<?php

namespace App\Jobs;

use App\Models\BusinessPartner;
use App\Services\SapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UploadPartnerFilesToSap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The business partner instance.
     *
     * @var \App\Models\BusinessPartner
     */
    public $partner;

    /**
     * Create a new job instance.
     */
    public function __construct(BusinessPartner $partner)
    {
        $this->partner = $partner;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sapService = new SapService();
        try {
            $requestId = (string) $this->partner->request_id;
            
            if (empty($requestId)) {
                Log::error('SAP File Upload Failed: Business Partner does not have a valid request_id attached.');
                return;
            }

            if (!empty($this->partner->tax_card)) {
                $taxCardResponse = $sapService->uploadFile($requestId . '#TAXCARD', $this->partner->tax_card);
                Log::info('SAP TAXCARD Upload Response: ', (array) $taxCardResponse);
            }

            if (!empty($this->partner->commercial_registration)) {
                $comCardResponse = $sapService->uploadFile($requestId . '#COMCARD', $this->partner->commercial_registration);
                Log::info('SAP COMCARD Upload Response: ', (array) $comCardResponse);
            }
            
            Log::info('SAP File Upload Successfully for partner: ' . $requestId);
        } catch (\Exception $e) {
            Log::error('SAP File Upload Job Failed: ' . $e->getMessage());
        }
    }
}
