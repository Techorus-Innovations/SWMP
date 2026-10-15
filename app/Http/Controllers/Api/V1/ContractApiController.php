<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetContractsApiRequest;
use App\Services\SapService;
use Illuminate\Http\JsonResponse;

class ContractApiController extends Controller
{
    use ApiResponses;

    public function index(GetContractsApiRequest $request): JsonResponse
    {
        $partner = $request->attributes->get('authenticated_partner');
        $bpCode = $partner->bp_code;
        $status = $request->query('status', 'active');
        $isPaginated = $request->has('page_size');

        // When pagination is omitted the spec requires the complete list: send no $top to SAP.
        $pageSize = $isPaginated ? (int) $request->query('page_size') : null;
        $pageNumber = $isPaginated ? (int) $request->query('page_number', 1) : 1;

        try {
            $contractsData = (new SapService())->getContracts($bpCode, $pageSize, $pageNumber, $status);
        } catch (\Throwable $e) {
            return $this->serviceUnavailable('contracts', $e);
        }

        // SapService returns the raw HTTP response (not an array) when SAP answers with an error
        if (! is_array($contractsData)) {
            return $this->serviceUnavailable('contracts', null, ['status' => $contractsData?->status()]);
        }

        $mappedContracts = collect($contractsData['paginatedContracts'] ?? [])->map(function ($contract) {
            return [
                'contract_code' => (string) ($contract['contract_code'] ?? ''),
                'contract_start' => $contract['contract_start'] ?? null,
                'contract_end' => $contract['contract_end'] ?? null,
                'storage_locations' => collect($contract['areas'] ?? [])->map(function ($area) {
                    return [
                        'storage_location_code' => (string) ($area['area_code'] ?? ''),
                        'storage_location_name' => (string) ($area['area_description'] ?? ''),
                        'contracted_storage_capacity' => (int) ($area['quantity'] ?? 0),
                        'contracted_storage_capacity_unit' => (string) ($area['measuring_unit'] ?? ''),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $totalCount = (int) ($contractsData['totalCount'] ?? count($mappedContracts));

        return response()->json([
            'total_count' => $totalCount,
            'contracts' => $mappedContracts,
        ], 200);
    }
}
