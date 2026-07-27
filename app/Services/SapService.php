<?php

namespace App\Services;

use App\Models\DraftOutbound;
use App\Models\Material;
use Harris21\Fuse\CircuitBreaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class SapService
{
    public CircuitBreaker $breaker;

    public function __construct(
        private string $baseUrl = '',
        private string $attpUrl = '',
        private string $username = '',
        private string $password = '',
        private string $client = '',
        private string $attpUsername = '',
        private string $attpPassword = '',
        private string $attpClient = '',
        private bool $verify = true,
    ) {
        $this->baseUrl = (string) config('services.sap.base_url');
        $this->username = (string) config('services.sap.username');
        $this->password = (string) config('services.sap.password');
        $this->client = (string) config('services.sap.client');
        $this->verify = (bool) config('services.sap.ssl_verify');
        $this->attpUrl = (string) config('services.sap.attp_url');
        $this->attpUsername = (string) config('services.sap.attp_username');
        $this->attpPassword = (string) config('services.sap.attp_password');
        $this->attpClient = (string) config('services.sap.attp_client');
        $this->breaker = new CircuitBreaker('sap');
    }

    private function guardCircuit()
    {
        if ($this->breaker->isOpen()) {
            throw new ServiceUnavailableHttpException(
                null,
                'External service is currently unavailable.'
            );
        }
    }

    private function failCircuit()
    {
        $this->breaker->recordFailure();
        throw new ServiceUnavailableHttpException(
            null,
            'External service is currently unavailable.'
        );
    }

    private function execute(callable $callback)
    {
        $this->guardCircuit();

        try {
            $result = $callback();
            $this->breaker->recordSuccess();

            return $result;

        } catch (\Throwable $e) {
            $this->failCircuit();
        }
    }

    private function getClient()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->withOptions(['verify' => $this->verify])
            ->acceptJson()
            ->withQueryParameters(['sap-client' => $this->client]);
    }

    private function getAttpClient()
    {
        return Http::withBasicAuth($this->attpUsername, $this->attpPassword)
            ->withOptions(['verify' => $this->verify])
            ->acceptJson()
            ->withQueryParameters(['sap-client' => $this->attpClient]);
    }

    public function getCsrfToken(): ?array
    {
        return $this->execute(function () {
            $response = $this->getClient()
                ->withHeaders(['x-csrf-token' => 'fetch'])
                ->get($this->baseUrl . '/sap/opu/odata/SAP/ZPRJ_ODATA_MATERIAL_SRV/ZC_MATERIAL');

            if ($response->successful()) {
                $token = $response->headers()['x-csrf-token'][0];

                return [
                    'token' => $token,
                    'headers' => $response->headers(),
                ];
            }
        });
    }

    public function getAttpCsrfToken(): ?array
    {
        return $this->execute(function () {
            $response = $this->getAttpClient()
                ->withHeaders(['x-csrf-token' => 'fetch'])
                ->get($this->attpUrl . '/sap/opu/odata/sap/ZAATP_FILE_SRV');

            if ($response->successful()) {
                $token = $response->headers()['x-csrf-token'][0];

                return [
                    'token' => $token,
                    'headers' => $response->headers(),
                    'cookies' => $response->cookies(),
                ];
            }
        });
    }

    public function getDeliverySlotsReservedCounts($date): ?array
    {
        return $this->execute(function () use ($date) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_TIME_SRV/ZC_TIME(D_Date=datetime'" . $date . "T00:00:00')/Set");

            if ($response->successful()) {
                $data = $response->json('d.results');

                $slots = collect($data)
                    ->pluck('delivery_count', 'delivery_hour')
                    ->toArray();

                return $slots;
            }
        });
    }

    public function getOutboundDeliverySlotsReservedCounts($date): ?array
    {
        return $this->execute(function () use ($date) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_TIME_OUTBOUND_SRV/ZC_TIME_outbound(D_Date=datetime'" . $date . "T00:00:00')/Set");

            if ($response->successful()) {
                $data = $response->json('d.results');

                $slots = collect($data)
                    ->pluck('delivery_count', 'delivery_hour')
                    ->toArray();

                return $slots;
            }
        });
    }

    public function getMaterials()
    {
        return $this->execute(function () {
            $response = $this->getClient()
                ->get($this->baseUrl . '/sap/opu/odata/SAP/ZPRJ_ODATA_MATERIAL_SRV/ZC_MATERIAL', [
                    '$expand' => 'to_mplant/to_mlocation',

                ]);

            if ($response->successful()) {
                $data = $response->json('d.results');

                $materials = collect($data)->map(function ($item) {
                    // Get the first plant from the results array (if exists)
                    // $plant = $item['to_mplant']['results'][0] ?? null;

                    // // Get the first location from the plant's nested results (if exists)
                    // $location = $plant['to_mlocation']['results'][0] ?? null;

                    return [
                        'material_code' => $item['material'],
                        'material_name' => $item['material_name'],
                        'material_type_code' => $item['material_type'],
                        'material_type_name' => $item['mat_type_des'],
                        'material_group_code' => $item['matgroup'],
                        'material_group_name' => $item['MATGROUPDES'],
                        'material_GTIN_code' => $item['mat_GTIN_code'],
                        'default_unit' => $item['unit_of_measure'],
                        'pal_to_default_unit' => $item['PAL_TO_EA_QUAN'],
                        'attp_required' => $item['ATTP_REQ'],
                        'plants' => collect($item['to_mplant']['results'] ?? [])->map(function ($plant) {
                            return [
                                'plant_code' => $plant['PLANT'],
                                'plant_name' => $plant['MATPALNATDES'],
                                // Map the locations (areas) nested inside each plant
                                'areas' => collect($plant['to_mlocation']['results'] ?? [])->map(function ($loc) {
                                    return [
                                        'area_code' => $loc['location'],
                                        'area_name' => $loc['MATLOCDES'],
                                    ];
                                })->all(),
                            ];
                        })->all(),
                        // 'plant'         => $plant['PLANT'] ?? NULL,
                        // 'location'      => $location['location'] ?? NULL,
                        // 'location_name' => $location['MATLOCDES'] ?? NULL,
                    ];
                });

                return $materials->toArray();
            }

        });

    }

    // public function getActiveContracts($businessPartnerCode,$pageSize,$pageNumber)
    // {
    //     $now = Carbon::now()->format('Y-m-d\TH:i:s');
    //     // dd($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_CONTRACT1_SRV/ZC_CONTRAC(c_kunnr={'$businessPartnerCode'})/Set");
    //     $response = Http::withBasicAuth($this->username, $this->password)
    //         ->withOptions(['verify' => $this->verify])
    //         ->withHeaders(['Accept' => 'application/json',])
    //         ->withQueryParameters([
    //             '$expand' => 'to_VP',
    //             'sap-client' => $this->client,
    //             '$top' => $pageSize,
    //             '$skip' => ($pageNumber-1)*$pageSize,
    //             '$filter' => "CONTRACT_END ge datetime'{$now}'",
    //         ])
    //         ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_CONTRACT1_SRV/ZC_CONTRAC(c_kunnr='$businessPartnerCode')/Set");

    //     if ($response->successful()) {
    //         $data = $response->json();

    //         $contracts = collect($data['d']['results'])->map(function ($item) {

    //         return [
    //                 'contract_code' => $item['Contract'],
    //                 'contract_start' => $this->parseSapDate($item['CONTRACT_START']),
    //                 'contract_end' => $this->parseSapDate($item['CONTRACT_END']),

    //                 'areas' => collect($item['to_VP']['results'] ?? [])->map(function ($area) {
    //                     return [
    //                         'area_code' => $area['Area_code'],
    //                         'area_description' => $area['Area_code_desc'],
    //                         'quantity' => $area['QUNTITY'],
    //                         'measuring_unit' => $area['MEINS']

    //                     ];
    //                 })
    //             ];
    //         })->all();

    //         return $contracts;

    //     }
    //     return $response;

    // }

    public function getContracts($businessPartnerCode, $pageSize, $pageNumber, $state)
    {
        return $this->execute(function () use ($businessPartnerCode, $pageSize, $pageNumber, $state) {
            $filter = $state == 'active' ? 'ge' : 'le';
            $now = Carbon::now()->format('Y-m-d\TH:i:s');
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_CONTRACT1_SRV/ZC_CONTRAC(c_kunnr='$businessPartnerCode')/Set", [
                    '$expand' => 'to_VP',
                    '$inlinecount' => 'allpages',
                    '$orderby' => 'CONTRACT_END asc',
                    '$top' => $pageSize,
                    '$skip' => ($pageNumber - 1) * $pageSize,
                    '$filter' => "CONTRACT_END {$filter} datetime'{$now}'",
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $contracts = collect($data['d']['results'])->map(function ($item) {

                    return [
                        'contract_code' => $item['Contract'],
                        'contract_start' => $this->parseSapDate($item['CONTRACT_START']),
                        'contract_end' => $this->parseSapDate($item['CONTRACT_END']),

                        'areas' => collect($item['to_VP']['results'] ?? [])->map(function ($area) {
                            return [
                                'area_code' => $area['Area_code_new'],
                                'area_description' => $area['Area_code_new_desc'],
                                'quantity' => $area['QUNTITY'],
                                'measuring_unit' => $area['MEINS'],

                            ];
                        }),
                    ];
                })->all();

                $contractsData = ['totalCount' => $data['d']['__count'], 'paginatedContracts' => $contracts];

                return $contractsData;
            }

            return $response;
        });
    }

    public function getContractById($businessPartnerCode, $contractId)
    {
        return $this->execute(function () use ($businessPartnerCode, $contractId) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_CONTRACT1_SRV/ZC_CONTRAC(c_kunnr='$businessPartnerCode')/Set", [
                    '$expand' => 'to_VP',
                    '$filter' => "Contract eq '$contractId'",
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $contracts = collect($data['d']['results'])->map(function ($item) {

                    return [
                        'contract_code' => $item['Contract'],
                        'contract_start' => $this->parseSapDate($item['CONTRACT_START']),
                        'contract_end' => $this->parseSapDate($item['CONTRACT_END']),

                        'areas' => collect($item['to_VP']['results'] ?? [])->map(function ($area) {
                            return [
                                'area_code' => $area['Area_code_new'],
                                'area_description' => $area['Area_code_new_desc'],
                                'quantity' => $area['QUNTITY'],
                                'measuring_unit' => $area['MEINS'],

                            ];
                        }),
                    ];
                })->all();

                return $contracts[0];
            }

            return $response;
        });

    }

    public function getStock($businessPartnerCode, $pageSize, $pageNumber)
    {
        return $this->execute(function () use ($businessPartnerCode, $pageSize, $pageNumber) {
            $response = $this->getClient()
                ->get($this->baseUrl . '/sap/opu/odata/sap/ZPRJ_ODATA_CDS_STOCK_SRV/ZI_Mat_stock', [
                    '$expand' => 'to_Inbounds',
                    '$inlinecount' => 'allpages',
                    '$filter' => "C_KUNNR eq '$businessPartnerCode' and Material ne ''",
                    '$orderby' => 'Material asc',
                    '$top' => $pageSize,
                    '$skip' => ($pageNumber - 1) * $pageSize,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $stock = collect($data['d']['results'])->map(function ($request) {

                    return [
                        'material_code' => $request['Material'],
                        'warehouse_code' => $request['Plant'],
                        'inbounds' => collect($request['to_Inbounds']['results'] ?? [])->map(function ($item) {
                            return [
                                'inbound_id' => $item['SalesDocument'],
                                'quantity_in_EA' => $item['stock'],
                                'quantity_in_PLA' => $item['stock_in_PAL'],
                                'pallet_unit' => $item['PalletUnit'],
                                'item_number' => $item['item_number'],
                                'location' => $item['location'],
                                'expiration_date' => $this->parseSapDate($item['expiration_date']),
                                'manufacture_date' => $this->parseSapDate($item['manf_date']),
                                'batch' => $item['batch'],
                            ];
                        }),
                    ];
                })->all();

                $stockData = ['totalCount' => $data['d']['__count'], 'paginatedStock' => $stock];

                return $stockData;
            }

            return $response;
        });

    }

    public function getAllStock($businessPartnerCode)
    {
        return $this->execute(function () use ($businessPartnerCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . '/sap/opu/odata/sap/ZPRJ_ODATA_CDS_STOCK_SRV/ZI_Mat_stock', [
                    '$expand' => 'to_Inbounds',
                    '$filter' => "C_KUNNR eq '$businessPartnerCode' and Material ne ''",
                    '$orderby' => 'Material asc',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $locale = app()->getLocale();

                $stock = collect($data['d']['results'])->map(function ($request) use ($locale) {
                    $material = Material::where('code', $request['Material'])->first();

                    return [
                        'material_name' => ($locale === 'ar' ? $material?->name_ar : $material?->name_en),
                        'material_code' => $request['Material'],
                        'warehouse_code' => $request['Plant'],
                        'inbounds' => collect($request['to_Inbounds']['results'] ?? [])->map(function ($item) {
                            return [
                                'inbound_id' => $item['SalesDocument'],
                                'quantity' => $item['stock'],
                                'measuring_unit' => $item['unit'],
                                'item_number' => $item['item_number'],
                                'location' => $item['location'],
                                'expiration_date' => $this->parseSapDate($item['expiration_date']),
                                'manufacture_date' => $this->parseSapDate($item['manf_date']),
                                'batch' => $item['batch'],
                            ];
                        }),
                    ];
                })->all();

                return $stock;
            }

            return $response;
        });

    }

    public function getStockByMaterialCodeAndWarehouseCode($businessPartnerCode, $materialCode, $warehouseCode)
    {
        return $this->execute(function () use ($businessPartnerCode, $materialCode, $warehouseCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . '/sap/opu/odata/sap/ZPRJ_ODATA_CDS_STOCK_SRV/ZI_Mat_stock', [
                    '$expand' => 'to_Inbounds',
                    '$inlinecount' => 'allpages',
                    '$filter' => "C_KUNNR eq '$businessPartnerCode' and Material ne '' and Material eq '$materialCode' and Plant eq '$warehouseCode'",
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $stock = collect($data['d']['results'])->map(function ($request) {

                    return [
                        'material_code' => $request['Material'],
                        'warehouse_code' => $request['Plant'],
                        'inbounds' => collect($request['to_Inbounds']['results'] ?? [])->map(function ($item) {
                            return [
                                'inbound_id' => $item['SalesDocument'],
                                'quantity_in_EA' => $item['stock'],
                                'quantity_in_PLA' => $item['stock_in_PAL'],
                                'pallet_unit' => $item['PalletUnit'],
                                'item_number' => $item['item_number'],
                                'location' => $item['location'],
                                'expiration_date' => $this->parseSapDate($item['expiration_date']),
                                'manufacture_date' => $this->parseSapDate($item['manf_date']),
                                'batch' => $item['batch'],
                            ];
                        }),
                    ];
                })->all();

                return $stock[0];

            }

            return $response;
        });

    }

    public function getOutboundRequests($businessPartnerCode, $pageSize, $pageNumber)
    {
        return $this->execute(function () use ($businessPartnerCode, $pageSize, $pageNumber) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/sap/ZPRJ_ODATA_VIEW_OUTBOUND_SRV/ZC_OUTVOUND_VIEW(C_KUNNR='$businessPartnerCode')/Set", [
                    '$expand' => 'to_LIP',
                    '$inlinecount' => 'allpages',
                    '$orderby' => 'Delivery_date desc',
                    '$top' => $pageSize,
                    '$skip' => ($pageNumber - 1) * $pageSize,
                ]);
            if ($response->successful()) {
                $this->breaker->recordSuccess();
                $data = $response->json();

                // dd($data);
                $outboundRequests = collect($data['d']['results'])->map(function ($request) {

                    return [
                        'outbound_request_id' => $request['Outbound_delivery'],
                        // 'delivery_date' => $this->parseSapDate($request['Delivery_date']),
                        'status' => $request['status'],
                        'delivery_time' => $this->parseSapTime($request['delivery_time']),
                        'delivery_date' => $this->parseSapDate($request['Delivery_date']),
                        'ship_to_party' => $request['ship_to_party'],
                        'items' => collect($request['to_LIP']['results'] ?? [])->map(function ($item) {
                            return [
                                'quantity' => $item['Delivery_quantity'],
                                'measuring_unit' => $item['Qunt'],
                                'material_code' => $item['material'],
                                'material_description' => $item['Description'],
                                'area_code' => $item['storage_location'],
                                'inbound_request_reference' => $item['Refernce'],
                            ];
                        }),
                    ];
                })->all();

                $outboundRequestsData = ['totalCount' => $data['d']['__count'], 'paginatedOutbounds' => $outboundRequests];

                return $outboundRequestsData;
            }

            return $response;
        });
    }

    public function getOutboundRequestById($businessPartnerCode, $outboundRequestCode)
    {
        return $this->execute(function () use ($businessPartnerCode, $outboundRequestCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/sap/ZPRJ_ODATA_VIEW_OUTBOUND_SRV/ZC_OUTVOUND_VIEW(C_KUNNR='$businessPartnerCode')/Set", [
                    '$expand' => 'to_LIP',
                    '$filter' => "Outbound_delivery eq '{$outboundRequestCode}'",

                ]);
            if ($response->successful()) {
                $this->breaker->recordSuccess();
                $data = $response->json();

                $outboundRequests = collect($data['d']['results'])->map(function ($request) {

                    return [
                        'outbound_request_id' => $request['Outbound_delivery'],
                        // 'delivery_date' => $this->parseSapDate($request['Delivery_date']),
                        'status' => $request['status'],
                        'delivery_time' => $this->parseSapTime($request['delivery_time']),
                        'delivery_date' => $this->parseSapDate($request['Delivery_date']),
                        'ship_to_party' => $request['ship_to_party'],
                        'items' => collect($request['to_LIP']['results'] ?? [])->map(function ($item) {
                            return [
                                'item_code' => $item['outbound_item'],
                                'quantity' => $item['Delivery_quantity'],
                                'measuring_unit' => $item['Qunt'],
                                'material_code' => $item['material'],
                                'material_description' => $item['Description'],
                                'area_code' => $item['storage_location'],
                                'inbound_request_reference' => $item['Refernce'],
                            ];
                        }),
                    ];
                })->all();

                return $outboundRequests[0];
            }

            return $response;
        });
    }

    // public function getInboundRequestById($businessPartnerCode, $inboundRequestCode)
    // {
    //     return $this->execute(function () use ($businessPartnerCode, $inboundRequestCode) {
    //         $response = $this->getClient()
    //             ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_VIEW_INB_REQ_SRV/ZC_VIEW_INBOUND(C_KUNNR='$businessPartnerCode')/Set", [
    //                 '$expand' => 'to_VBAP',
    //                 '$filter' => "Inbound_request eq '{$inboundRequestCode}'",
    //             ]);

    //         if ($response->successful()) {
    //             $data = $response->json();

    //             $inboundRequests = collect($data['d']['results'])->map(function ($request) {
    //                 return [
    //                     'inbound_request_id' => $request['Inbound_request'],
    //                     'created_date' => $this->parseSapDate($request['Created_on']),
    //                     'shipping_indicator' => $request['ship_indicator'],
    //                     'delivery_time' => $this->parseSapTime($request['delivery_time']),
    //                     'delivery_date' => $this->parseSapDate($request['delivery_date']),

    //                     'items' => collect($request['to_VBAP']['results'] ?? [])->map(function ($item) {
    //                         return [
    //                             'quantity' => $item['QUNTITY'],
    //                             'measuring_unit' => $item['MEINS'],
    //                             'material_code' => $item['Area_code'],
    //                             'material_description' => $item['Area_code_desc'],
    //                             'status' => $item['STATUS_DESC'],
    //                         ];
    //                     }),
    //                 ];
    //             })->all();
    //             return $inboundRequests[0];
    //         }
    //         return $response;
    //     });

    // }

    public function getInboundRequests($businessPartnerCode, $pageSize, $pageNumber)
    {
        // dd($businessPartnerCode, $pageSize, $pageNumber);
        return $this->execute(function () use ($businessPartnerCode, $pageSize, $pageNumber) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_VIEW_INB_REQ_SRV/ZC_VIEW_INBOUND(C_KUNNR='$businessPartnerCode')/Set", [
                    '$expand' => 'to_VBAP',
                    '$inlinecount' => 'allpages',
                    '$orderby' => 'Created_on desc',
                    '$top' => $pageSize,
                    '$skip' => ($pageNumber - 1) * $pageSize,
                ]);

            if ($response->successful()) {

                $this->breaker->recordSuccess();
                $data = $response->json();


                $inboundRequests = collect($data['d']['results'])->map(function ($request) {
                    if ($request['Delivery'] == "") {
                        $status = "Cancelled";
                        $delivery_date = "";
                        $delivery_time = "";
                    } else {
                        $status = "Active";
                        $delivery_date = $this->parseSapDate($request['delivery_date']);
                        $delivery_time = $this->parseSapTime($request['delivery_time']);

                    }

                    return [
                        'inbound_request_id' => $request['Inbound_request'],
                        'contract' => $request['Contract_number'],
                        'created_date' => $this->parseSapDate($request['Created_on']),
                        'shipping_indicator' => $request['ship_indicator'],
                        'status' => $status,
                        'delivery_date' => $delivery_date,
                        'delivery_time' => $delivery_time,

                        'items' => collect($request['to_VBAP']['results'] ?? [])->map(function ($item) {
                            return [
                                'quantity' => $item['QUNTITY'],
                                'measuring_unit' => $item['MEINS'],
                                'material_code' => $item['Area_code'],
                                'material_description' => $item['Area_code_desc'],
                                'batch' => $item['batch'],
                                'manufacturing_date' => $this->parseSapDate($item['Manufacturing_date']),
                                'expiration_date' => $this->parseSapDate($item['Expiration_date']),
                            ];
                        }),
                    ];
                })->all();

                $inboundRequestsData = ['totalCount' => $data['d']['__count'], 'paginatedInbounds' => $inboundRequests];

                return $inboundRequestsData;
            }

            return $response;
        });
    }

    public function getInboundRequestById($businessPartnerCode, $inboundRequestCode)
    {
        return $this->execute(function () use ($businessPartnerCode, $inboundRequestCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_VIEW_INB_REQ_SRV/ZC_VIEW_INBOUND(C_KUNNR='$businessPartnerCode')/Set", [
                    '$expand' => 'to_VBAP',
                    '$filter' => "Inbound_request eq '{$inboundRequestCode}'",
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $inboundRequests = collect($data['d']['results'])->map(function ($request) {
                    return [
                        'inbound_request_id' => $request['Inbound_request'],
                        'created_date' => $this->parseSapDate($request['Created_on']),
                        'shipping_indicator' => $request['ship_indicator'],
                        'delivery_time' => $this->parseSapTime($request['delivery_time']),
                        'delivery_date' => $this->parseSapDate($request['delivery_date']),

                        'items' => collect($request['to_VBAP']['results'] ?? [])->map(function ($item) {
                            return [
                                'quantity' => $item['QUNTITY'],
                                'measuring_unit' => $item['MEINS'],
                                'material_code' => $item['Area_code'],
                                'material_description' => $item['Area_code_desc'],
                                'status' => $item['STATUS_DESC'],
                            ];
                        }),
                    ];
                })->all();

                return $inboundRequests[0];
            }

            return $response;
        });

    }


    public function getDetailedInboundRequest($businessPartnerCode, $inboundRequestCode)
    {
        $response = $this->getClient()
            ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_VIEW_INB_REQ_SRV/ZC_VIEW_INBOUND(C_KUNNR='$businessPartnerCode')/Set", [
                '$expand' => 'to_VBAP',
                '$filter' => "Inbound_request eq '{$inboundRequestCode}'",
            ]);
        if ($response->successful()) {
            $inboundRequest = $response->json()["d"]["results"][0];
            $inboundDeliveryCode = $inboundRequest['Delivery'];
                // dd($inboundRequest);
            if ($inboundDeliveryCode == "") {
                return [
                    'inbound_request_id' => $inboundRequestCode,
                    'contract' => $inboundRequest['Contract_number'],
                    'created_date' => $this->parseSapDate($inboundRequest['Created_on']),
                    'status' => "Cancelled",
                    'delivery_time' => "",
                    'delivery_date' => "",

                    'items' => collect($inboundRequest['to_VBAP']['results'] ?? [])->map(function ($item) {

                        return [
                            'quantity' => $item['QUNTITY'],
                            'measuring_unit' => $item['MEINS'],
                            'material_code' => $item['Area_code'],
                            'material_description' => $item['Area_code_desc'],
                            'batch' => $item['batch'],
                            'manufacturing_date' => $this->parseSapDate($item['Manufacturing_date']),
                            'expiration_date' => $this->parseSapDate($item['Expiration_date']),
                            'received_quantity' => 0,
                            'quantity_unit' => $item['MEINS'],
                            'status_code' => "c",
                        ];
                    }),
                ];
            } else {

                $inboundDelivery = $this->getInboundDeliveryWithItems($inboundDeliveryCode);
                if ($inboundDelivery) {
                    $deliveryItems = collect($inboundDelivery['items'] ?? [])->keyBy('material');

                    return [
                        'inbound_request_id' => $inboundRequestCode,
                        'contract' => $inboundRequest['Contract_number'],
                        'created_date' => $this->parseSapDate($inboundRequest['Created_on']),
                        'status' => "Active",
                        'delivery_time' => $this->parseSapTime($inboundRequest['delivery_time']),
                        'delivery_date' => $this->parseSapDate($inboundRequest['delivery_date']),

                        'items' => collect($inboundRequest['to_VBAP']['results'] ?? [])->map(function ($item) use ($deliveryItems) {
                            $materialCode = $item['Area_code'];
                            $deliveryInfo = $deliveryItems->get($materialCode);
                            if ($deliveryInfo['planned_quantity'] == 0) {
                                $status_code = "c";
                            } else if ($deliveryInfo['recieved_quantity'] > 0) {
                                $status_code = "r";
                            } else {
                                $status_code = "n";
                            }

                            return [
                                'quantity' => $item['QUNTITY'],
                                'measuring_unit' => $item['MEINS'],
                                'material_code' => $item['Area_code'],
                                'material_description' => $item['Area_code_desc'],
                                'batch' => $item['batch'],
                                'manufacturing_date' => $this->parseSapDate($item['Manufacturing_date']),
                                'expiration_date' => $this->parseSapDate($item['Expiration_date']),
                                'received_quantity' => (integer) $deliveryInfo['recieved_quantity'] ?? 0,
                                'quantity_unit' => $deliveryInfo['quantity_unit'] ?? $item['MEINS'],
                                'status_code' => $status_code ?? null,
                            ];
                        }),
                    ];
                }
            }
        }

            return false;
        }

    
    // public function getDetailedInboundRequest($businessPartnerCode, $inboundRequestCode)
    // {
    //     return $this->execute(function () use ($businessPartnerCode, $inboundRequestCode) {
    //         $inboundRequest = $this->getInboundRequestDetails($inboundRequestCode);
    //         // dd($inboundRequest);
    //         if ($inboundRequest) {

    //             $inboundDeliveryCode = $inboundRequest['inbound_delivery'];
    //             $inboundDelivery = $this->getInboundDeliveryWithItems($inboundDeliveryCode);
    //             if ($inboundDelivery) {
    //                 $response = $this->getClient()
    //                     ->get($this->baseUrl."/sap/opu/odata/SAP/ZPRJ_ODATA_VIEW_INB_REQ_SRV/ZC_VIEW_INBOUND(C_KUNNR='$businessPartnerCode')/Set", [
    //                         '$expand' => 'to_VBAP',
    //                         '$filter' => "Inbound_request eq '{$inboundRequestCode}'",
    //                     ]);
    //                     // dd($response->json());
    //                 if ($response->successful()) {
    //                     $data = $response->json();
    //                     dd(collect($inboundDelivery['items'] ?? [])->keyBy('material'));

    //                     $inboundRequests = collect($data['d']['results'][0])->map(function ($request) use ($inboundDelivery) {
    //                         $deliveryItems = collect($inboundDelivery['items'] ?? [])->keyBy('material');




    //                         return [
    //                             'inbound_request_id' => $request['Inbound_request'],
    //                             'created_date' => $this->parseSapDate($request['Created_on']),
    //                             'shipping_indicator' => $request['ship_indicator'],
    //                             'delivery_time' => $this->parseSapTime($request['delivery_time']),
    //                             'delivery_date' => $this->parseSapDate($request['delivery_date']),

    //                             'items' => collect($request['to_VBAP']['results'] ?? [])->map(function ($item) use ($deliveryItems) {
    //                                 $materialCode = $item['Area_code'];
    //                                 $deliveryInfo = $deliveryItems->get($materialCode);
    //                                 if ($deliveryInfo['planned_quantity'] == 0){
    //                                     $status_code = "c";
    //                                 }
    //                                 else if ($deliveryInfo['recieved_quantity'] > 0){
    //                                     $status_code = "r";
    //                                 }
    //                                 else{
    //                                     $status_code = "n";
    //                                 }
    //                                 return [
    //                                     'quantity' => $item['QUNTITY'],
    //                                     'measuring_unit' => $item['MEINS'],
    //                                     'material_code' => $item['Area_code'],
    //                                     'material_description' => $item['Area_code_desc'],
    //                                     // 'status' => $item['STATUS_DESC'],
    //                                     'received_quantity' => (integer)$deliveryInfo['recieved_quantity'] ?? 0,
    //                                     'planned_quantity' => $deliveryInfo['planned_quantity'] ?? 0,
    //                                     'quantity_unit' => $deliveryInfo['quantity_unit'] ?? $item['MEINS'],
    //                                     'status_code' => $status_code ?? null,
    //                                 ];
    //                             }),
    //                         ];
    //                     })->all();

    //                     // return [
    //                     //     'inbound_request' => $inboundRequests[0],
    //                     //     'inbound_delivery' => $inboundDelivery,
    //                     // ];
    //                     return $inboundRequests[0];

    //                 }

    //             }

    //         }

    //         return false;
    //     });

    // }

    public function cancelOutboundRequestBatch($outboundRequestCode)
    {
        $outboundRequestItems = $this->getOutboundRequestItems($outboundRequestCode);

        if ($outboundRequestItems && $outboundRequestItems->isNotEmpty()) {
            $OutboundDeliveryItemsCancelationStatus = $this->batchCancelOutboundItems($outboundRequestCode, $outboundRequestItems);

            if (!$OutboundDeliveryItemsCancelationStatus) {
                return false;
            }



        }



        $OutboundCancelationStatus = $this->CancelOutboundFromErp($outboundRequestCode);

        return $OutboundCancelationStatus;
    }

    public function cancelInboundRequestBatch($inboundRequestCode)
    {
        $inboundRequest = $this->getInboundRequestDetails($inboundRequestCode);
        if ($inboundRequest) {
            $inboundDeliveryCode = $inboundRequest['inbound_delivery'];
            $inboundPo = $inboundRequest['po_number'];

            $deliveryItems = $this->getInboundDeliveryItems($inboundDeliveryCode);

            if ($deliveryItems->isNotEmpty()) {
                $InboundDeliveryItemsCancelationStatus = $this->batchCancelInboundDeliveryItems($inboundDeliveryCode, $deliveryItems);

                if (!$InboundDeliveryItemsCancelationStatus) {
                    return false;
                }

            }

            $PoCancelationStatus = $this->CancelPo($inboundPo);
            if (!$PoCancelationStatus) {
                return false;
            }

            $salesOrderItems = $this->getSalesOrderItems($inboundRequestCode);
            // dd($salesOrderItems);

            if ($salesOrderItems->isNotEmpty()) {
                $InboundDeliveryItemsCancelationStatus = $this->batchCancelSalesOrderItems($inboundRequestCode, $salesOrderItems);

                if (!$InboundDeliveryItemsCancelationStatus) {
                    return false;
                }

            }

            return true;

        }
    }

    public function batchCancelInboundDeliveryItems($inboundDeliveryCode, $deliveryItems)
    {

        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];
        $CRLF = "\r\n";

        $batchData = '--batch_1' . $CRLF;
        $batchData .= 'Content-Type: multipart/mixed; boundary=changeset_s1' . $CRLF . $CRLF;

        $counter = 1;
        foreach ($deliveryItems as $item) {

            $etag = '*'; // $item['etag'];
            $itemCode = $item['item_code'];
            $batchData .= '--changeset_s1' . $CRLF;
            $batchData .= 'Content-Type: application/http' . $CRLF;
            $batchData .= 'Content-Transfer-Encoding: binary' . $CRLF;
            $batchData .= 'Content-ID: ' . $counter . $CRLF . $CRLF;
            $batchData .= "POST WhseInboundDeliveryItem(EWMInboundDelivery='" . $inboundDeliveryCode . "',EWMInboundDeliveryItem='" . $itemCode . "')/SAP__self.AdjustDeliveryItemQuantity HTTP/1.1" . $CRLF;
            $batchData .= 'Content-Type: application/json' . $CRLF;
            $batchData .= 'If-Match: ' . $etag . $CRLF . $CRLF;
            $batchData .= '{}' . $CRLF;
            $counter++;
        }

        $batchData .= '--changeset_s1--' . $CRLF;
        $batchData .= '--batch_1--';

        // dd($batchData);

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Cookie' => $cookies,
                'Content-Type' => 'multipart/mixed;boundary=batch_1',
                'Accept' => 'multipart/mixed, application/json',
            ])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->withBody($batchData, 'multipart/mixed;boundary=batch_1')
            ->post($this->baseUrl . '/sap/opu/odata4/sap/api_whse_inb_delivery_2/srvd_a2x/sap/warehouseinbounddelivery/0001/$batch');

        return $response->status() == 200;
    }

    public function batchCancelSalesOrderItems($inboundRequestCode, $salesOrderItems)
    {

        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];
        $CRLF = "\r\n";

        $batchData = '--batch_1' . $CRLF;
        $batchData .= 'Content-Type: multipart/mixed; boundary=changeset_s1' . $CRLF . $CRLF;

        $counter = 1;
        foreach ($salesOrderItems as $item) {

            $etag = '*'; // $item['etag'];
            $salesOrderItem = $item['sales_order_item'];
            $batchData .= '--changeset_s1' . $CRLF;
            $batchData .= 'Content-Type: application/http' . $CRLF;
            $batchData .= 'Content-Transfer-Encoding: binary' . $CRLF;
            $batchData .= 'Content-ID: ' . $counter . $CRLF . $CRLF;
            $batchData .= "PATCH SalesOrderItem(SalesOrder='" . $inboundRequestCode . "',SalesOrderItem='" . $salesOrderItem . "') HTTP/1.1" . $CRLF;
            $batchData .= 'Content-Type: application/json' . $CRLF;
            $batchData .= 'If-Match: ' . $etag . $CRLF . $CRLF;
            $batchData .= '{"SalesDocumentRjcnReason": "02"}' . $CRLF;
            $counter++;
        }

        $batchData .= '--changeset_s1--' . $CRLF;
        $batchData .= '--batch_1--';

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Cookie' => $cookies,
                'Content-Type' => 'multipart/mixed;boundary=batch_1',
                'Accept' => 'multipart/mixed, application/json',
            ])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->withBody($batchData, 'multipart/mixed;boundary=batch_1')
            ->post($this->baseUrl . '/sap/opu/odata4/sap/api_salesorder/srvd_a2x/sap/salesorder/0001/$batch');

        // dd($response->body());
        return $response->status() == 200;

    }

    public function CancelInboundRequest($inboundRequestCode)
    {
        $inboundRequest = $this->getInboundRequestDetails($inboundRequestCode);
        if ($inboundRequest) {
            $inboundDeliveryCode = $inboundRequest['inbound_delivery'];
            $inboundPo = $inboundRequest['po_number'];

            $deliveryItems = $this->getInboundDeliveryItems($inboundDeliveryCode);
            // dd($inboundDeliveryCode,  $deliveryItems);

            foreach ($deliveryItems as $item) {

                $etag = '*'; // $item['etag'];
                $itemCode = $item['item_code'];
                $DeliveryItemCancelationStatus = $this->CancelInboundDeliveryItems($inboundDeliveryCode, $itemCode, $etag);
                if (!$DeliveryItemCancelationStatus) {
                    return false;
                }
            }

            $PoCancelationStatus = $this->CancelPo($inboundPo);
            if (!$PoCancelationStatus) {
                return false;
            }
            $SalesOrderItems = $this->getSalesOrderItems($inboundRequestCode);
            // dd($SalesOrderItems);
            foreach ($SalesOrderItems as $item) {

                $etag = '*'; // $item['etag'];
                $itemCode = $item['sales_order_item'];
                $SalesOrderItemCancelationStatus = $this->CancelSalesOrderItems($inboundRequestCode, $itemCode, $etag);
                if ($SalesOrderItemCancelationStatus != 'true') {
                    return false;

                    return $SalesOrderItemCancelationStatus;
                }
            }

            return true;

        }
    }


    public function batchCancelOutboundItems($outboundCode, $outboundItems)
    {

        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];
        $CRLF = "\r\n";

        $batchData = '--batch_1' . $CRLF;
        $batchData .= 'Content-Type: multipart/mixed; boundary=changeset_s1' . $CRLF . $CRLF;

        $counter = 1;
        foreach ($outboundItems as $item) {

            $etag = '*'; // $item['etag'];
            $itemCode = $item['item_code'];
            $batchData .= '--changeset_s1' . $CRLF;
            $batchData .= 'Content-Type: application/http' . $CRLF;
            $batchData .= 'Content-Transfer-Encoding: binary' . $CRLF;
            $batchData .= 'Content-ID: ' . $counter . $CRLF . $CRLF;
            $batchData .= "POST WhseOutboundDeliveryOrderItem(EWMOutboundDeliveryOrder='" . $outboundCode . "',EWMOutboundDeliveryOrderItem='" . $itemCode . "')//SAP__self.AdjustDeliveryItemQuantity HTTP/1.1" . $CRLF;
            $batchData .= 'Content-Type: application/json' . $CRLF;
            $batchData .= 'If-Match: ' . $etag . $CRLF . $CRLF;
            $batchData .= '{}' . $CRLF;
            $counter++;
        }

        $batchData .= '--changeset_s1--' . $CRLF;
        $batchData .= '--batch_1--';

        // dd($batchData);

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Cookie' => $cookies,
                'Content-Type' => 'multipart/mixed;boundary=batch_1',
                'Accept' => 'multipart/mixed, application/json',
            ])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->withBody($batchData, 'multipart/mixed;boundary=batch_1')
            ->post($this->baseUrl . '/sap/opu/odata4/sap/api_warehouse_odo_2/srvd_a2x/sap/warehouseoutbdeliveryorder/0001/$batch');

        return $response->status() == 200;
    }


    public function CancelOutboundFromErp($outboundRequestCode)
    {
        return $this->execute(function () use ($outboundRequestCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_OUTBOUND_CANCEL_SRV/outbound_deliverySet('" . $outboundRequestCode . "')");
            // dd($response->json());
            if ($response->successful()) {
                return true;
            }
            return false;
        });
    }



    public function getOutboundRequestItems($outboundRequestCode)
    {
        return $this->execute(function () use ($outboundRequestCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata4/sap/api_warehouse_odo_2/srvd_a2x/sap/warehouseoutbdeliveryorder/0001/WhseOutboundDeliveryOrderHead('" . $outboundRequestCode . "')", [
                    '$expand' => '_WhseOutbDeliveryOrderItem',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // dd($data);

                $deliveryItems = collect($data['_WhseOutbDeliveryOrderItem'])->map(function ($request) {
                    return [
                        'etag' => $request['@odata.etag'],
                        'item_code' => $request['EWMOutboundDeliveryOrderItem'],
                        'status' => $request['GoodsIssueStatus'],
                    ];
                });
                return $deliveryItems->where('status', '1');
            }
            return false;
        });
    }

    public function getInboundRequestDetails($inboundRequestCode)
    {
        return $this->execute(function () use ($inboundRequestCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . '/sap/opu/odata/sap/ZPRJ_GET_INB_PO_SO_SRV/ZI_LIPS', [
                    '$filter' => "SO eq '{$inboundRequestCode}'",
                ]);
            if ($response->successful()) {
                $data = $response->json();

                $inboundRequests = collect($data['d']['results'])->map(function ($request) {
                    return [
                        'inbound_delivery' => $request['inbound_delivery'],
                        // 'po_number' => $request['po_number'],
                    ];
                })->all();

                return $inboundRequests[0];
            }

            return false;
        });
    }

    public function getInboundDeliveryItems($inboundDeliveryCode)
    {
        return $this->execute(function () use ($inboundDeliveryCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata4/sap/api_whse_inb_delivery_2/srvd_a2x/sap/warehouseinbounddelivery/0001/WhseInboundDeliveryHead(EWMInboundDelivery='$inboundDeliveryCode')", [
                    '$expand' => '_WhseInbDeliveryItem',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // dd($data);
                $deliveryItems = collect($data['_WhseInbDeliveryItem'])->map(function ($request) {
                    return [
                        'etag' => $request['@odata.etag'],
                        'goods_receipt_status' => $request['GoodsReceiptStatus'],
                        'item_code' => $request['EWMInboundDeliveryItem'],
                    ];
                });

                return $deliveryItems->where('goods_receipt_status', '1');
                // return $inboundRequests[0];
            }
        });
    }

    // public function getInboundDeliveryWithItems($inboundDeliveryCode)
    // {
    //     return $this->execute(function () use ($inboundDeliveryCode) {
    //         $response = $this->getClient()
    //             ->get($this->baseUrl."/sap/opu/odata4/sap/api_whse_inb_delivery_2/srvd_a2x/sap/warehouseinbounddelivery/0001/WhseInboundDeliveryHead(EWMInboundDelivery='$inboundDeliveryCode')", [
    //                 '$expand' => '_WhseInbDeliveryItem',
    //             ]);

    //         if ($response->successful()) {
    //             $data = $response->json();

    //             $dateTime = $this->parseSapDateTime($data['PlannedDeliveryUTCDateTime']);
    //             $date = $dateTime->toDateString();
    //             $time = $dateTime->toTimeString();

    //             $deliveryItems = collect($data['_WhseInbDeliveryItem'])->map(function ($request) {
    //                 return [
    //                     'material' => $request['Product'],
    //                     'recieved_quantity' => $request['ProductQuantity'],
    //                     'recieved_quantity_unit' => $request['QuantityUnit'],
    //                     'received_status' => $request['CompletionStatus'],
    //                 ];
    //             });

    //             $inboundDelivery = [
    //                 'inbound_delivery_id' => $inboundDeliveryCode,
    //                 'planned_delivery_date' => $date,
    //                 'planned_delivery_time' => $time,

    //                 'items' => collect($data['_WhseInbDeliveryItem'])->map(function ($request) {
    //                     return [
    //                         'material' => (string) (int) $request['Product'],
    //                         'recieved_quantity' => $request['ProductQuantity'],
    //                         'recieved_quantity_unit' => $request['QuantityUnit'],
    //                         'received_status' => $request['CompletionStatus'],
    //                     ];
    //                 }),
    //             ];

    //             return $inboundDelivery;
    //         }

    //         return false;
    //     });
    // }


    public function getInboundDeliveryWithItems($inboundDeliveryCode)
    {

        return $this->execute(function () use ($inboundDeliveryCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata/sap/ZI_EWM_DELIVERY_CDS/ZI_EWM_DELIVERY(p_inbound='$inboundDeliveryCode')/Set", [
                    '$expand' => 'to_GR',
                ]);



            if ($response->successful()) {
                $inboundDelivery = $response->json()['d']['results'][0];
                $dateTime = $this->parseSapDateTime($this->parseSapDateTimestamp($inboundDelivery['planned_date']));
                $date = $dateTime->toDateString();
                $time = $dateTime->toTimeString();

                $inboundDeliveryData = [
                    'inbound_delivery_id' => $inboundDeliveryCode,
                    'planned_delivery_date' => $date,
                    'planned_delivery_time' => $time,

                    'items' => collect($inboundDelivery['to_GR']['results'])->map(function ($request) {
                        return [
                            'material' => $request['product'],
                            'recieved_quantity' => $request['RECIVIED_QUANTITY'],
                            'planned_quantity' => $request['planned_quantity'],
                            'quantity_unit' => $request['unit'],
                            // 'received_status' => $request['CompletionStatus'],
                        ];
                    }),
                ];
                return $inboundDeliveryData;
            }

            return false;
        });
    }


    public function getSalesOrderItems($inboundRequestCode)
    {
        return $this->execute(function () use ($inboundRequestCode) {
            $response = $this->getClient()
                ->get($this->baseUrl . "/sap/opu/odata4/sap/api_salesorder/srvd_a2x/sap/salesorder/0001/SalesOrder('$inboundRequestCode')", [
                    '$expand' => '_Item',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // dd($data);
                $SalesOrderItems = collect($data['_Item'])->map(function ($request) {
                    return [
                        'etag' => $request['@odata.etag'],
                        'sales_order_item' => $request['SalesOrderItem'],
                        'status' => $request['SDProcessStatus'],
                    ];
                });

                return $SalesOrderItems;

                return $SalesOrderItems->where('status', 'A');
                // return $inboundRequests[0];
            }

            return false;
        });
    }

    public function CancelSalesOrderItems($inboundRequestCode, $salesOrderItem, $etag)
    {
        $data = [
            'SalesDocumentRjcnReason' => '02',
        ];
        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Accept' => 'application/json',
                'Cookie' => $cookies,
                'if-match' => $etag,
            ])
            // ->withHeaders(['X-Requested-With' => 'X', 'Accept' => 'application/json'])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->patch($this->baseUrl . "/sap/opu/odata4/sap/api_salesorder/srvd_a2x/sap/salesorder/0001/SalesOrderItem(SalesOrder='$inboundRequestCode',SalesOrderItem='$salesOrderItem')", $data);

        // return $response->json();
        // dd($response->json());
        if ($response->successful()) {
            return 'true';
        }

        return $response->json();

    }

    public function CancelInboundDeliveryItems($inboundDeliveryCode, $inboundDeliveryItem, $etag)
    {

        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Accept' => 'application/json',
                'Cookie' => $cookies,
                'if-match' => $etag,
            ])
            // ->withHeaders(['X-Requested-With' => 'X', 'Accept' => 'application/json'])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->post($this->baseUrl . "/sap/opu/odata4/sap/api_whse_inb_delivery_2/srvd_a2x/sap/warehouseinbounddelivery/0001/WhseInboundDeliveryItem(EWMInboundDelivery='$inboundDeliveryCode',EWMInboundDeliveryItem='$inboundDeliveryItem')/SAP__self.AdjustDeliveryItemQuantity");

        // return $response->json();

        if ($response->successful()) {
            return true;
        }

        return false;

    }

    public function CancelPo($PoCode)
    {

        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Accept' => 'application/json',
                'Cookie' => $cookies,
            ])
            // ->withHeaders(['X-Requested-With' => 'X', 'Accept' => 'application/json'])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->delete($this->baseUrl . "/sap/opu/odata4/sap/api_purchaseorder_2/srvd_a2x/sap/purchaseorder/0001/PurchaseOrder('$PoCode')");

        // return $response->json();
        dd($response->json());
        if ($response->successful()) {
            return true;
        }

        return false;

    }

    public function createPartner($request_id, $request_type, $partner_group_code, $name1, $name2, $gln_loc_no_1, $gln_loc_no_2, $gln_check_digit, $city, $street, $house_no, $postal_code, $country_code, $region_code, $tax_number, $commercial_registration_number, $industry_code, $customer_type_code, $tax_category, $mobile, $email)
    {
        $data = [
            'd' => [
                'REQ_ID' => $request_id,
                'REQ_TYPE' => $request_type,
                'PART_GROUP' => $partner_group_code,
                'TAXTYPEEG0' => $commercial_registration_number,
                'TAXTYPEEG1' => $tax_number,
                'INDUSTRY_TYPE' => $industry_code,
                'CUST_TYPE' => $customer_type_code,
                'TAX_CAT' => $tax_category,
                'ZCENT_DATA_ORG' => [
                    'LOC_NO_1' => $gln_loc_no_1,
                    'LOC_NO_2' => $gln_loc_no_2,
                    'CHECK_DOGIT' => $gln_check_digit,
                    'NAME1' => $name1,
                    'NAME2' => $name2,
                    'NAME3' => '',
                    'NAME4' => '',
                ],
                'ZADDRESS_DATA' => [
                    'CITY' => $city,
                    'STREET' => $street,
                    'HOUSE_NO' => $house_no,
                    'POSTAL_CODE' => $postal_code,
                    'LOCATION' => '',
                    'BUILDING' => '',
                    'COUNTRY' => $country_code,
                    'REGION' => $region_code,
                ],
                'ZCONTACT_DATA' => [
                    'TELE_NUM1' => $mobile,
                    'EMAIL_DATA' => $email,
                ],
            ],
        ];
        // dd($data);
        $response = $this->getClient()
            ->withHeaders(['X-Requested-With' => 'X'])
            ->post($this->baseUrl . '/sap/opu/odata/SAP/ZINT_PORTAL_SRV/PARTNER', $data);

        // dd($response->json());
        return $response->json();

        // if ($response->successful()) {
        //     $token = $response->headers()["x-csrf-token"][0];
        //     return $token;
        // }

    }

    public function createInbound($businessPartnerCode, $contractCode, $items, $date, $hour)
    {
        $counter = 10;
        $results = [];
        foreach ($items as $material => $quantity) {
            $formatted = sprintf('%06d', $counter);
            array_push(
                $results,
                [
                    'SoNumber' => $contractCode,
                    'ITEM' => $formatted,
                    'Materia' => (string) $material,
                    'ReqQty' => (string) $quantity,
                ]
            );
            $counter += 10;
        }

        $timestampSeconds = strtotime($date);

        $timestampMilliseconds = $timestampSeconds * 1000;

        $sapDateFormat = '/Date(' . $timestampMilliseconds . ')/';

        $data = [
            'd' => [

                'BpNum' => $businessPartnerCode,
                'ReceivingDate' => $sapDateFormat, // '\/Date(1772150400000)\/',
                'ShipmentIndicator' => '0',
                'SoNumber' => $contractCode,
                'deliverytime' => 'PT' . $hour . 'H00M00S',
                'soitemsSet' => [
                    'results' => $results,
                ],
            ],
        ];

        // dd(json_encode($data));

        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Accept' => 'application/json',
                'Cookie' => $cookies,
            ])
            // ->withHeaders(['X-Requested-With' => 'X', 'Accept' => 'application/json'])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->post($this->baseUrl . '/sap/opu/odata/SAP/ZPRJ_INB_REQQ_SRV/soheaderSet', $data);

        return $response->json();

        // if ($response->successful()) {
        //     $token = $response->headers()["x-csrf-token"][0];
        //     return $token;
        // }

    }

    public function createOutbound($businessPartnerCode, $shiptoPartyCode, $outboundId, $date, $hour)
    {

        $sapDateFormat = $date . 'T00:00:00';
        $sapTimeFormat = 'PT' . $hour . 'H00M00S';

        $outbound = DraftOutbound::where('id', $outboundId)->first();
        $outboundedMaterials = $outbound->materials->where('quantity', '>', 0);
        $inboundRequests = $outboundedMaterials->pluck('inbound_request_id')->unique();
        $adjustShipToPartyStatus = $this->adjustSalesOrdersShipToParty($inboundRequests, $shiptoPartyCode);
        if ($adjustShipToPartyStatus) {
            // dd($outboundedMaterials);
            $OutboundDeliveryResponse = $this->createOutboundDelivery($outboundedMaterials);
            if ($OutboundDeliveryResponse->successful()) {
                $OutboundDeliveryCode = $OutboundDeliveryResponse->json()['d']['DeliveryDocument'];
                $updateOutboundDeliveryDateTimeStatus = $this->updateOutboundDeliveryDateTime($OutboundDeliveryCode, $sapDateFormat, $sapTimeFormat);
                if ($updateOutboundDeliveryDateTimeStatus) {
                    $OutboundRequest = $this->getOutboundRequestById($businessPartnerCode, $OutboundDeliveryCode);
                    $OutboundRequestItems = $OutboundRequest['items'];
                    $adjustOutboundDeliveryItemsStorageStatus = $this->adjustOutboundDeliveryItemsStorage($OutboundDeliveryCode, $OutboundRequestItems, $outboundedMaterials);
                    if ($adjustOutboundDeliveryItemsStorageStatus) {
                        return $OutboundDeliveryCode;
                    }
                }
            }
        }
    }

    public function adjustOutboundDeliveryItemsStorage($OutboundDeliveryCode, $OutboundRequestItems, $outboundedMaterials)
    {

        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];
        $CRLF = "\r\n";

        $batchData = '--batch_1' . $CRLF;
        $batchData .= 'Content-Type: multipart/mixed; boundary=changeset_s1' . $CRLF . $CRLF;

        $counter = 1;
        foreach ($OutboundRequestItems as $item) {
            $itemCode = $item['item_code'];
            $outboundedMaterial = $outboundedMaterials->where('inbound_request_id', $item['inbound_request_reference'])->where('material_code', $item['material_code'])->first();
            $itemLocation = $outboundedMaterial['location'];

            $etag = '*'; // $item['etag'];
            $batchData .= '--changeset_s1' . $CRLF;
            $batchData .= 'Content-Type: application/http' . $CRLF;
            $batchData .= 'Content-Transfer-Encoding: binary' . $CRLF;
            $batchData .= 'Content-ID: ' . $counter . $CRLF . $CRLF;
            $batchData .= "PATCH A_OutbDeliveryItem(DeliveryDocument='" . $OutboundDeliveryCode . "',DeliveryDocumentItem='" . $itemCode . "') HTTP/1.1" . $CRLF;
            $batchData .= 'Content-Type: application/json' . $CRLF;
            $batchData .= 'If-Match: ' . $etag . $CRLF . $CRLF;
            $batchData .= '{"StorageLocation": "' . $itemLocation . '"}' . $CRLF;
            $batchData .= '--changeset_s1--' . $CRLF . $CRLF;
            $counter++;
        }
        $batchData .= '--batch_1--';

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Cookie' => $cookies,
                'Content-Type' => 'multipart/mixed;boundary=batch_1',
                'Accept' => 'multipart/mixed, application/json',
            ])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->withBody($batchData, 'multipart/mixed;boundary=batch_1')
            ->post($this->baseUrl . '/sap/opu/odata/sap/API_OUTBOUND_DELIVERY_SRV/$batch');

        return $response->successful();

    }

    public function updateOutboundDeliveryDateTime($OutboundDeliveryCode, $sapDateFormat, $sapTimeFormat)
    {
        $etag = '*';
        $data = [
            'DeliveryDate' => $sapDateFormat,
            'DeliveryTime' => $sapTimeFormat,
        ];
        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Accept' => 'application/json',
                'Cookie' => $cookies,
                'if-match' => $etag,
            ])
            // ->withHeaders(['X-Requested-With' => 'X', 'Accept' => 'application/json'])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->patch($this->baseUrl . "/sap/opu/odata/sap/API_OUTBOUND_DELIVERY_SRV/A_OutbDeliveryHeader(DeliveryDocument='$OutboundDeliveryCode')", $data);

        // return $response->json();
        // dd($response);
        // if ($response->successful()) {
        //     return true;
        // }
        return $response->successful();
        // return $response->json();

    }

    public function createOutboundDelivery($outboundedMaterials)
    {
        $results = [];
        foreach ($outboundedMaterials as $material) {
            array_push(
                $results,
                [
                    'ReferenceSDDocument' => $material->inbound_request_id,
                    'ReferenceSDDocumentItem' => $material->inbound_request_item_code,
                    'ActualDeliveryQuantity' => (string) $material->quantity,
                    'DeliveryQuantityUnit' => $material->quantity_unit,
                ]
            );
        }

        // $timestampSeconds = strtotime($date);

        // $timestampMilliseconds = $timestampSeconds * 1000;

        // $sapDateFormat = '/Date('.$timestampMilliseconds.')/';

        $data = [
            'to_DeliveryDocumentItem' => [
                'results' => $results,
            ],
        ];

        // dd(json_encode($data));

        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Accept' => 'application/json',
                'Cookie' => $cookies,
            ])
            // ->withHeaders(['X-Requested-With' => 'X', 'Accept' => 'application/json'])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->post($this->baseUrl . '/sap/opu/odata/sap/API_OUTBOUND_DELIVERY_SRV/A_OutbDeliveryHeader', $data);

        // dd($response->body());
        return $response;

        // if ($response->successful()) {
        //     $token = $response->headers()["x-csrf-token"][0];
        //     return $token;
        // }

    }

    public function adjustSalesOrdersShipToParty($salesOrdersCodes, $shiptoPartyCode)
    {
        //
        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];
        $CRLF = "\r\n";

        $batchData = '--batch_1' . $CRLF;
        $batchData .= 'Content-Type: multipart/mixed; boundary=changeset_s1' . $CRLF . $CRLF;

        $counter = 1;
        foreach ($salesOrdersCodes as $salesOrdersCode) {

            $etag = '*'; // $item['etag'];
            $batchData .= '--changeset_s1' . $CRLF;
            $batchData .= 'Content-Type: application/http' . $CRLF;
            $batchData .= 'Content-Transfer-Encoding: binary' . $CRLF;
            $batchData .= 'Content-ID: ' . $counter . $CRLF . $CRLF;
            $batchData .= "PATCH A_SalesOrderHeaderPartner(SalesOrder='" . $salesOrdersCode . "',PartnerFunction='WE') HTTP/1.1" . $CRLF;
            $batchData .= 'Content-Type: application/json' . $CRLF;
            $batchData .= 'If-Match: ' . $etag . $CRLF . $CRLF;
            $batchData .= '{"Customer": "' . $shiptoPartyCode . '"}' . $CRLF;
            $batchData .= '--changeset_s1--' . $CRLF . $CRLF;
            $counter++;
        }
        $batchData .= '--batch_1--';

        $response = $this->getClient()
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Cookie' => $cookies,
                'Content-Type' => 'multipart/mixed;boundary=batch_1',
                'Accept' => 'multipart/mixed, application/json',
            ])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->withBody($batchData, 'multipart/mixed;boundary=batch_1')
            ->post($this->baseUrl . '/sap/opu/odata/sap/API_SALES_ORDER_SRV/$batch');

        // dd($response->successful());
        return $response->successful();

    }

    private function parseSapDate($sapDate)
    {
        if (!$sapDate) {
            return null;
        }

        // Extract the digits (milliseconds) from the string
        preg_match('/\d+/', $sapDate, $matches);

        if (isset($matches[0])) {
            // Convert milliseconds to seconds and create Carbon instance
            return Carbon::createFromTimestamp($matches[0] / 1000)->format('Y-m-d');
        }

        return null;
    }
    private function parseSapDateTimestamp($sapDateTimestamp)
    {
        if (!$sapDateTimestamp) {
            return null;
        }

        // Extract the digits (milliseconds) from the string
        preg_match('/\d+/', $sapDateTimestamp, $matches);

        if (isset($matches[0])) {
            // Convert milliseconds to seconds and create Carbon instance
            return Carbon::createFromTimestamp($matches[0] / 1000)->format('Y-m-d\TH:i:s');
        }

        return null;
    }
    private function parseSapTime($sapDuration)
    {
        return Carbon::createFromFormat('H', substr($sapDuration, 2, 2))->format('h A');
    }

    private function parseSapDateTime($sapDateTime)
    {
        if (!$sapDateTime) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d\TH:i:s', substr($sapDateTime, 0, 19));
    }

    public function uploadFile($slug, $filePath)
    {
        $fileContent = \Illuminate\Support\Facades\Storage::disk('private')->get($filePath);
        if (!$fileContent) {
            return null;
        }

        $tokenResponse = $this->getCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookies = $tokenResponse['headers']['set-cookie'];

        $response = $this->getClient()
            ->withOptions([
                'verify' => $this->verify,
            ])
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Accept' => 'application/json',
                'SLUG' => $slug,
                'Content-Type' => 'application/octet-stream',
                'Cookie' => $cookies,
            ])
            ->withQueryParameters(['sap-client' => $this->client])
            ->withBody($fileContent, 'application/octet-stream')
            ->post($this->baseUrl . '/sap/opu/odata/SAP/ZTEST_SEND_FILES_SRV/FileSet');

        return $response->json();
    }

    public function uploadAttpFile($filePath)
    {
        $fileContent = File::get($filePath);
        if (!$fileContent) {
            return null;
        }

        $tokenResponse = $this->getAttpCsrfToken();
        $csrfToken = $tokenResponse['token'];
        $cookie1 = $tokenResponse['cookies']->getCookieByName('SAP_SESSIONID_ATP_120')->getValue();
        $cookie2 = $tokenResponse['cookies']->getCookieByName('sap-usercontext')->getValue();

        $response = $this->getAttpClient()
            ->withOptions([
                'verify' => $this->verify,
            ])
            ->withHeaders([
                'x-csrf-token' => $csrfToken,
                'Accept' => 'application/json',
                'SLUG' => (string) Carbon::now(),
                'Content-Type' => 'application/octet-stream',
                'Cookie' => "SAP_SESSIONID_ATP_120=$cookie1; sap-usercontext=$cookie2",
            ])
            ->withQueryParameters(['sap-client' => $this->client])
            ->withBody($fileContent, 'application/octet-stream')
            ->post($this->attpUrl . '/sap/opu/odata/sap/ZAATP_FILE_SRV/FileSet');
        // dd($response->json());

        return $response->successful();
    }
}
