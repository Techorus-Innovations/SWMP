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


    // /**
    //  * Get all groups for a course.
    //  * @param int $courseId Moodle course ID
    //  * @return array Array of group data
    //  */
    public function getMaterials(): array
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get($this->baseUrl . '/sap/opu/odata/SAP/ZPRJ_ODATA_MATERIAL_SRV/ZC_MATERIAL')
            ->withOptions(['verify' => $verify])
            ->withHeaders(['Accept' => 'application/json',])
            ->withQueryParameters([
                '$expand' => 'to_mplant/to_mlocation',
                'sap-client' => $this->client,
            ]);

        if ($response->successful()) {
            return $response->json();
        }

    }

}
