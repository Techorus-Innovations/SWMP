<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessPartner;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PartnerStatusController extends Controller
{
    public function updatePartnerStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => 'required|string|exists:business_partners,request_id',
            'status' => 'required|in:A,R',
            'bp_code' => 'required_if:status,A|nullable|string',
        ]);

        $businessPartner = BusinessPartner::where('request_id', $validated['request_id'])->first();

        if (!$businessPartner) {
            return response()->json([
                'success' => false,
                'message' => 'Business partner not found',
            ], 404);
        }

        $businessPartner->status = $validated['status'];
        
        if ($validated['status'] === 'A' && isset($validated['bp_code'])) {
            $businessPartner->bp_code = $validated['bp_code'];
        } else {
            $businessPartner->bp_code = null;
        }
        $businessPartner->save();

        return response()->json([
            'success' => true,
            'message' => 'Partner status updated successfully',
            'data' => [
                'request_id' => $businessPartner->request_id,
                'status' => $businessPartner->status,
                'bp_code' => $businessPartner->bp_code,
            ],
        ], 200);
    }
}
