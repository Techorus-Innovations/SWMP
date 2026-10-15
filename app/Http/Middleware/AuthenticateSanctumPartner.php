<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSanctumPartner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return response()->json([
                'message' => 'Unauthorized: Invalid or missing token.',
            ], 401);
        }

        $tokenRecord = PersonalAccessToken::findToken($bearerToken);

        if (! $tokenRecord) {
            return response()->json([
                'message' => 'Unauthorized: Invalid or missing token.',
            ], 401);
        }

        // Check token expiration (24h)
        if ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast()) {
            $tokenRecord->delete();
            return response()->json([
                'message' => 'Unauthorized: Bearer token has expired. Please request a new token.',
            ], 401);
        }

        $client = $tokenRecord->tokenable;

        if (! ($client instanceof Client)) {
            return response()->json([
                'message' => 'Unauthorized: Invalid or missing token.',
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
                'message' => 'Forbidden: Associated Business Partner account is pending or rejected',
            ], 403);
        }

        // Touch last used timestamp
        $tokenRecord->forceFill(['last_used_at' => now()])->save();
        $client->updateQuietly(['last_used_at' => now()]);

        // Inject authenticated client and partner into request attributes
        $request->attributes->set('authenticated_client', $client);
        $request->attributes->set('authenticated_partner', $partner);

        return $next($request);
    }
}
