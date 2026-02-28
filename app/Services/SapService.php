<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SapService
{
    public function __construct(
        private string $baseUrl = '',
        private string $username = '',
        private string $password = '',
        private string $client = '',
        private bool $verify = true,
    ) {
        $this->baseUrl = (string) config('services.sap.base_url');
        $this->username = (string) config('services.sap.username');
        $this->password = (string) config('services.sap.password');
        $this->client = (string) config('services.sap.client');
        $this->verify = (bool) config('services.sap.ssl_verify');
    }


    public function getCsrfToken(): ?string
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withOptions(['verify' => $this->verify])
            ->withHeaders(['x-csrf-token' => 'fetch'])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->get($this->baseUrl . '/sap/opu/odata/SAP/ZPRJ_ODATA_MATERIAL_SRV/ZC_MATERIAL');


        if ($response->successful()) {
            $token = $response->headers()["x-csrf-token"][0];
            return $token;
        }



    }


    public function getMaterials()
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withOptions(['verify' => $this->verify])
            ->withHeaders(['Accept' => 'application/json',])
            ->withQueryParameters([
                '$expand' => 'to_mplant/to_mlocation',
                'sap-client' => $this->client,

            ])
            ->get($this->baseUrl . '/sap/opu/odata/SAP/ZPRJ_ODATA_MATERIAL_SRV/ZC_MATERIAL');

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
        $filter = $state == 'active' ? 'ge' : 'le';
        $now = Carbon::now()->format('Y-m-d\TH:i:s');
        // dd($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_CONTRACT1_SRV/ZC_CONTRAC(c_kunnr={'$businessPartnerCode'})/Set");
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withOptions(['verify' => $this->verify])
            ->withHeaders(['Accept' => 'application/json',])
            ->withQueryParameters([
                '$expand' => 'to_VP',
                '$inlinecount' => 'allpages',
                'sap-client' => $this->client,
                '$orderby' => 'CONTRACT_END asc',
                '$top' => $pageSize,
                '$skip' => ($pageNumber - 1) * $pageSize,
                '$filter' => "CONTRACT_END {$filter} datetime'{$now}'",
            ])
            ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_CONTRACT1_SRV/ZC_CONTRAC(c_kunnr='$businessPartnerCode')/Set");

        if ($response->successful()) {
            $data = $response->json();

            $contracts = collect($data['d']['results'])->map(function ($item) {

                return [
                    'contract_code' => $item['Contract'],
                    'contract_start' => $this->parseSapDate($item['CONTRACT_START']),
                    'contract_end' => $this->parseSapDate($item['CONTRACT_END']),

                    'areas' => collect($item['to_VP']['results'] ?? [])->map(function ($area) {
                        return [
                            'area_code' => $area['Area_code'],
                            'area_description' => $area['Area_code_desc'],
                            'quantity' => $area['QUNTITY'],
                            'measuring_unit' => $area['MEINS']

                        ];
                    })
                ];
            })->all();

            $contractsData = ['totalCount' => $data['d']['__count'], 'paginatedContracts' => $contracts];

            return $contractsData;

        }
        return $response;

    }


    

    public function getInboundRequests($businessPartnerCode, $pageSize, $pageNumber)
    {
           $response = Http::withBasicAuth($this->username, $this->password)
            ->withOptions(['verify' => $this->verify])
            ->withHeaders(['Accept' => 'application/json',])
            ->withQueryParameters([
                '$expand' => 'to_VBAP',
                '$inlinecount' => 'allpages',
                'sap-client' => $this->client,
                '$orderby' => 'Created_on desc',
                '$top' => $pageSize,
                '$skip' => ($pageNumber - 1) * $pageSize,
            ])
            ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_VIEW_INB_REQ_SRV/ZC_VIEW_INBOUND(C_KUNNR='$businessPartnerCode')/Set");

        if ($response->successful()) {
            $data = $response->json();

            // dd($data);
            $inboundRequests = collect($data['d']['results'])->map(function ($request) {

                return [
                    'inbound_request_id' => $request['Inbound_request'],
                    'created_date' => $this->parseSapDate($request['Created_on']),
                    'shipping_indicator' => $request['ship_indicator'],

                    'items' => collect($request['to_VBAP']['results'] ?? [])->map(function ($item) {
                        return [
                            'quantity' => $item['QUNTITY'],
                            'measuring_unit' => $item['MEINS'],
                            'material_code' => $item['Area_code'],
                            'material_description' => $item['Area_code_desc'],
                            'status' => $item['STATUS_DESC'],
                        ];
                    })
                ];
            })->all();

            $inboundRequestsData = ['totalCount' => $data['d']['__count'], 'paginatedInbounds' => $inboundRequests];

            return $inboundRequestsData;

        }
        return $response;

    }


    public function getInboundRequestById($businessPartnerCode,$inboundRequestCode)
    {
           $response = Http::withBasicAuth($this->username, $this->password)
            ->withOptions(['verify' => $this->verify])
            ->withHeaders(['Accept' => 'application/json',])
            ->withQueryParameters([
                '$expand' => 'to_VBAP',
                'sap-client' => $this->client,
                '$filter' => "Inbound_request eq '{$inboundRequestCode}'",
            ])
            ->get($this->baseUrl . "/sap/opu/odata/SAP/ZPRJ_ODATA_VIEW_INB_REQ_SRV/ZC_VIEW_INBOUND(C_KUNNR='$businessPartnerCode')/Set");

        if ($response->successful()) {
            $data = $response->json();

            $inboundRequests = collect($data['d']['results'])->map(function ($request) {

                return [
                    'inbound_request_id' => $request['Inbound_request'],
                    'created_date' => $this->parseSapDate($request['Created_on']),
                    'shipping_indicator' => $request['ship_indicator'],

                    'items' => collect($request['to_VBAP']['results'] ?? [])->map(function ($item) {
                        return [
                            'quantity' => $item['QUNTITY'],
                            'measuring_unit' => $item['MEINS'],
                            'material_code' => $item['Area_code'],
                            'material_description' => $item['Area_code_desc'],
                            'status' => $item['STATUS_DESC'],
                        ];
                    })
                ];
            })->all();

            return $inboundRequests[0];
        }
        return $response;

    }

    public function createPartner($request_id, $request_type, $partner_group_code, $name1, $name2, $gln_loc_no_1, $gln_loc_no_2, $gln_check_digit, $city, $street, $house_no, $postal_code, $country_code, $region_code, $tax_number, $commercial_registration_number, $industry_code, $customer_type_code, $tax_category, $mobile, $email)
    {
        $data = [
            "d" => [
                "REQ_ID" => $request_id,
                "REQ_TYPE" => $request_type,
                "PART_GROUP" => $partner_group_code,
                "TAXTYPEEG0" => $commercial_registration_number,
                "TAXTYPEEG1" => $tax_number,
                "INDUSTRY_TYPE" => $industry_code,
                "CUST_TYPE" => $customer_type_code,
                "TAX_CAT" => $tax_category,
                "ZCENT_DATA_ORG" => [
                    "LOC_NO_1" => $gln_loc_no_1,
                    "LOC_NO_2" => $gln_loc_no_2,
                    "CHECK_DOGIT" => $gln_check_digit,
                    "NAME1" => $name1,
                    "NAME2" => $name2,
                    "NAME3" => "",
                    "NAME4" => ""
                ],
                "ZADDRESS_DATA" => [
                    "CITY" => $city,
                    "STREET" => $street,
                    "HOUSE_NO" => $house_no,
                    "POSTAL_CODE" => $postal_code,
                    "LOCATION" => "",
                    "BUILDING" => "",
                    "COUNTRY" => $country_code,
                    "REGION" => $region_code
                ],
                "ZCONTACT_DATA" => [
                    "TELE_NUM1" => $mobile,
                    "EMAIL_DATA" => $email
                ],
            ]
        ];
        // dd($data);
        $response = Http::withBasicAuth($this->username, $this->password)
            ->withOptions(['verify' => $this->verify])
            ->withHeaders(['X-Requested-With' => 'X', 'Accept' => 'application/json'])
            ->withQueryParameters([
                'sap-client' => $this->client,
            ])
            ->post($this->baseUrl . '/sap/opu/odata/SAP/ZINT_PORTAL_SRV/PARTNER', $data);
        // dd($response->json());
        return $response->json();

        // if ($response->successful()) {
        //     $token = $response->headers()["x-csrf-token"][0];
        //     return $token;
        // }



    }

    private function parseSapDate($sapDate)
    {
        if (!$sapDate)
            return null;

        // Extract the digits (milliseconds) from the string
        preg_match('/\d+/', $sapDate, $matches);

        if (isset($matches[0])) {
            // Convert milliseconds to seconds and create Carbon instance
            return Carbon::createFromTimestamp($matches[0] / 1000)->format('Y-m-d');
        }

        return null;
    }


}
