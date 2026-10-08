<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateInboundApiRequest;
use App\Models\DraftInbound;
use App\Models\DraftInboundMaterial;
use App\Models\Material;
use App\Services\SapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InboundApiController extends Controller
{
    public function store(CreateInboundApiRequest $request): JsonResponse
    {
        $partner = $request->attributes->get('authenticated_partner');
        $bpCode = $partner->bp_code;
        $contractCode = $request->input('contract_code', $request->input('contract_id'));
        $date = $request->input('delivery_date');
        $hour = $request->input('delivery_time', $request->input('delivery_time_slot'));
        $products = $request->input('products', $request->input('materials', []));
        $userId = $partner->user_id ?? $partner->users()->first()?->id ?? 1;

        $draft = null;

        try {
            DB::beginTransaction();

            $draft = DraftInbound::create([
                'title' => 'API Inbound ' . now()->format('Y-m-d H:i:s'),
                'contract_id' => $contractCode,
                'user_id' => $userId,
            ]);

            foreach ($products as $item) {
                $gtin = $item['gtin'] ?? ($item['gtin_code'] ?? null);
                $material = Material::where('gtin_code', $gtin)->first();

                // Auto-resolve storage location from material's first area in deterministic order
                $plantArea = $material->areas()->orderBy('material_area.id')->first();

                DraftInboundMaterial::create([
                    'draft_inbound_id' => $draft->id,
                    'material_id' => $material->id,
                    'plant_area_id' => $plantArea?->id,
                    'quantity' => (int) $item['quantity'],
                    'batch' => $item['batch'],
                    'manufacture_date' => $item['manufacture_date'],
                    'expiration_date' => $item['expiration_date'],
                ]);
            }

            DB::commit();

            // Load draft relations for SAP payload formatting
            $draft->load(['materials']);

            $sapService = new SapService();
            $response = $sapService->createInbound($bpCode, $contractCode, $draft, $date, $hour);

            preg_match('/Inbound Request(\d+)/', $response['d']['Message'] ?? '', $matches);
            $requestNumber = $matches[1] ?? null;

            // Delete staging draft
            $draft->delete();

            if (! $requestNumber) {
                $errorMsg = $response['d']['Message']
                    ?? $response['error']['message']['value']
                    ?? 'Failed to create inbound in SAP.';

                Log::warning('SAP Inbound Creation Failed', ['response' => $response]);

                return response()->json([
                    'message' => $errorMsg,
                ], 502);
            }

            return response()->json([
                'inbound_id' => (string) $requestNumber,
                'message' => 'Inbound request created successfully in SAP.',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($draft && $draft->exists) {
                $draft->delete();
            }

            Log::error('Inbound API store error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'message' => 'Internal server error while processing inbound request: ' . $e->getMessage(),
            ], 500);
        }
    }
}
