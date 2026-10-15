<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthApiController extends Controller
{
    public function issueToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ], [
            'client_id.required' => 'The client_id field is required.',
            'client_secret.required' => 'The client_secret field is required.',
        ]);

        if ($validator->fails()) {
            $formattedErrors = [];
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $formattedErrors[] = [
                        'code' => 'MISSING_ATTRIBUTE',
                        'field' => $field,
                        'message' => 'Missing attribute: ' . $message,
                    ];
                }
            }

            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $formattedErrors,
            ], 422);
        }

        $client = Client::with('businessPartner')
            ->where('client_id', $request->input('client_id'))
            ->first();

        if (! $client || ! Hash::check($request->input('client_secret'), $client->client_secret)) {
            return response()->json([
                'message' => 'Invalid client_id or client_secret.',
            ], 401);
        }

        if (! $client->is_active) {
            return response()->json([
                'message' => 'Forbidden: API credentials have been deactivated.',
            ], 403);
        }

        $partner = $client->businessPartner;
        if (! $partner || $partner->status !== 'A' || empty($partner->bp_code)) {
            return response()->json([
                'message' => 'Forbidden: Associated Business Partner account is pending or rejected.',
            ], 403);
        }

        // Issue 24-hour Bearer token via Laravel Sanctum
        $token = $client->createToken('api-token', ['*'], now()->addDay());

        $client->updateQuietly(['last_used_at' => now()]);

        return response()->json([
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'business_partner' => [
                'bp_code' => $partner->bp_code,
                'name' => $partner->name1,
            ],
        ], 200);
    }
}
