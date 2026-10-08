<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateOutboundApiRequest;
use App\Models\DraftOutbound;
use App\Models\DraftOutboundMaterial;
use App\Models\Material;
use App\Services\SapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutboundApiController extends Controller
{
    public function store(CreateOutboundApiRequest $request): JsonResponse
    {
        $partner = $request->attributes->get('authenticated_partner');
        $bpCode = $partner->bp_code;
        $shipToPartyGln = $request->input('ship_to_party_gln', $request->input('ship_to_party'));
        $deliveryService = (bool) $request->input('delivery_service', false);
        $date = $request->input('delivery_date');
        $hour = $request->input('delivery_time_slot');
        $products = $request->input('products', []);

        $outbound = null;

        try {
            DB::beginTransaction();

            $outbound = DraftOutbound::create([
                'ship_to_party' => $shipToPartyGln,
                'business_partner_id' => $partner->id,
            ]);

            $sapService = new SapService();
            $stockData = $sapService->getAllStock($bpCode) ?? [];

            // Index stock by material_code
            $stockByMatCode = [];
            foreach ($stockData as $sItem) {
                $stockByMatCode[$sItem['material_code']] = $sItem;
            }

            foreach ($products as $item) {
                $gtin = $item['product_gtin'] ?? ($item['gtin'] ?? ($item['material_code'] ?? ''));
                $material = Material::where('gtin_code', $gtin)->first() ?? Material::where('code', $gtin)->first();
                $matCode = $material?->code ?? $gtin;

                $stockItem = $stockByMatCode[$matCode] ?? null;
                $batchItem = collect($stockItem['batches'] ?? [])
                    ->firstWhere('batch_code', $item['batch_code']);

                $locations = $batchItem['locations'] ?? [];
                if (empty($locations)) {
                    $firstArea = $material?->areas()->first();
                    $locations = [[
                        'location_code' => $firstArea?->code ?? '1001',
                        'location_quantity' => (int) $item['quantity'],
                    ]];
                }

                $remainingQty = (int) $item['quantity'];
                foreach ($locations as $loc) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    $locQty = (float) ($loc['location_quantity'] ?? 0);
                    $allocated = min($remainingQty, (int) $locQty);
                    if ($allocated <= 0) {
                        $allocated = $remainingQty;
                    }

                    DraftOutboundMaterial::create([
                        'draft_outbound_id' => $outbound->id,
                        'material_code' => $matCode,
                        'quantity' => $allocated,
                        'max_quantity' => $allocated,
                        'quantity_unit' => $batchItem['batch_quantity_unit'] ?? 'EA',
                        'location_code' => $loc['location_code'],
                        'batch_code' => $item['batch_code'],
                        'manufacture_date' => $batchItem['manufacture_date'] ?? null,
                        'expiration_date' => $batchItem['expiration_date'] ?? null,
                    ]);

                    $remainingQty -= $allocated;
                }
            }

            DB::commit();

            // Load outbound materials relations needed for SAP payload
            $outbound->load(['outboundMaterials.plantArea.plant']);

            $response = $sapService->createOutbound($bpCode, $shipToPartyGln, $outbound->id, $date, $hour, $deliveryService);

            // Clean up staging record
            $outbound->delete();

            if (! empty($response['error'])) {
                $errorMsg = $response['error']['message']['value'] ?? 'Failed to create outbound in SAP.';
                Log::warning('SAP Outbound Creation Failed', ['response' => $response]);

                // Map SAP ship-to partner rejection to INVALID_GLN error
                $lowerMsg = strtolower($errorMsg);
                if (str_contains($lowerMsg, 'partner') || str_contains($lowerMsg, 'bploc') || str_contains($lowerMsg, 'customer') || str_contains($lowerMsg, 'gln')) {
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => [
                            [
                                'code' => 'INVALID_GLN',
                                'field' => 'ship_to_party_gln',
                                'message' => "Invalid GLN: The destination GLN / Ship-to Party '{$shipToPartyGln}' is invalid.",
                            ],
                        ],
                    ], 422);
                }

                return response()->json([
                    'message' => $errorMsg,
                ], 502);
            }

            return response()->json([
                'outbound_id' => 'O' . $outbound->id,
                'message' => 'Outbound request created successfully in SAP.',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($outbound && $outbound->exists) {
                $outbound->delete();
            }

            Log::error('Outbound API store error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'message' => 'Internal server error while processing outbound request: ' . $e->getMessage(),
            ], 500);
        }
    }
}
