<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetStockApiRequest;
use App\Models\Material;
use App\Models\PlantArea;
use App\Services\SapService;
use Illuminate\Http\JsonResponse;

class StockApiController extends Controller
{
    public function index(GetStockApiRequest $request): JsonResponse
    {
        $partner = $request->attributes->get('authenticated_partner');
        $bpCode = $partner->bp_code;
        $productGtin = $request->query('product_gtin');
        $materialCodeFallback = $request->query('material_code');
        $isPaginated = $request->has('page_size');

        try {
            $sapService = new SapService();

            if ($productGtin) {
                $material = Material::where('gtin_code', $productGtin)->first();
                if (! $material) {
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => [
                            [
                                'code' => 'PRODUCT_GTIN_NOT_FOUND',
                                'field' => 'product_gtin',
                                'message' => "Product GTIN not found: Material with GTIN '{$productGtin}' does not exist or has no available stock.",
                            ],
                        ],
                    ], 422);
                }

                $allStock = $sapService->getAllStock($bpCode) ?? [];
                $stockItems = collect($allStock)
                    ->where('material_code', $material->code)
                    ->values()
                    ->all();

                if (empty($stockItems)) {
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => [
                            [
                                'code' => 'PRODUCT_GTIN_NOT_FOUND',
                                'field' => 'product_gtin',
                                'message' => "Product GTIN not found: Material with GTIN '{$productGtin}' does not exist or has no available stock.",
                            ],
                        ],
                    ], 422);
                }
            } elseif ($materialCodeFallback) {
                $allStock = $sapService->getAllStock($bpCode) ?? [];
                $stockItems = collect($allStock)
                    ->where('material_code', $materialCodeFallback)
                    ->values()
                    ->all();
            } elseif ($isPaginated) {
                $pageSize = (int) $request->query('page_size');
                $pageNumber = (int) $request->query('page_number', 1);
                $stockData = $sapService->getStock($bpCode, $pageSize, $pageNumber);
                $stockItems = $stockData['paginatedStock'] ?? [];
            } else {
                $stockItems = $sapService->getAllStock($bpCode) ?? [];
            }

            // Preload materials and plant areas for GTIN and location name lookups
            $materialCodes = collect($stockItems)->pluck('material_code')->filter()->unique()->values();
            $materials = Material::whereIn('code', $materialCodes)->get()->keyBy('code');

            $locationCodes = collect($stockItems)
                ->flatMap(fn ($item) => collect($item['batches'] ?? [])
                    ->flatMap(fn ($batch) => collect($batch['locations'] ?? [])->pluck('location_code')))
                ->filter()
                ->unique()
                ->values();
            $plantAreas = PlantArea::whereIn('code', $locationCodes)->get()->keyBy('code');

            $mappedProducts = collect($stockItems)->map(function ($item) use ($materials, $plantAreas) {
                $matCode = (string) ($item['material_code'] ?? '');
                $material = $materials->get($matCode);
                $gtin = $material?->gtin_code ?? $matCode;

                $mappedBatches = collect($item['batches'] ?? [])->map(function ($batch) use ($plantAreas) {
                    $mappedLocations = collect($batch['locations'] ?? [])->map(function ($loc) use ($plantAreas) {
                        $locCode = (string) ($loc['location_code'] ?? '');
                        $area = $plantAreas->get($locCode);
                        $locName = $area?->name_en ?: ($loc['location_code'] ?? 'LOC-01');

                        return [
                            'storage_location_name' => $locName,
                            'quantity_in_location' => (float) ($loc['location_quantity'] ?? 0),
                        ];
                    })->values()->all();

                    return [
                        'batch_code' => (string) ($batch['batch_code'] ?? ''),
                        'batch_quantity' => (float) ($batch['batch_quantity'] ?? 0),
                        'expiration_date' => $batch['expiration_date'] ?? null,
                        'manufacture_date' => $batch['manufacture_date'] ?? null,
                        'storage_locations' => $mappedLocations,
                    ];
                })->values()->all();

                return [
                    'product_gtin' => (string) $gtin,
                    'total_quantity' => (float) ($item['material_quantity'] ?? 0),
                    'total_quantity_unit' => (string) ($item['material_quantity_unit'] ?? 'EA'),
                    'batches' => $mappedBatches,
                ];
            })->values()->all();

            $totalCount = isset($stockData['totalCount']) ? (int) $stockData['totalCount'] : count($mappedProducts);

            return response()->json([
                'total_count' => $totalCount,
                'products' => $mappedProducts,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve stock from SAP: ' . $e->getMessage(),
            ], 502);
        }
    }
}
