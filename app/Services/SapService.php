<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

 
    public function getMaterials(): array
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
                    'material_code'            => $item['material'],
                    'material_name'          => $item['material_name'],
                    'material_type_code'            => $item['material_type'],
                    'material_type_name'          => $item['mat_type_des'],
                    'material_group_code'            => $item['matgroup'],
                    'material_group_name'          => $item['MATGROUPDES'],
                
                    'plants' => collect($item['to_mplant']['results'] ?? [])->map(function ($plant) {
                        return [
                            'plant_code'     => $plant['PLANT'],
                            'plant_name'  => $plant['MATPALNATDES'],
                            // Map the locations (areas) nested inside each plant
                            'areas' => collect($plant['to_mlocation']['results'] ?? [])->map(function ($loc) {
                                return [
                                    'area_code'   => $loc['location'],
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

    

}
