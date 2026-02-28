<?php

namespace App\Jobs;

use App\Models\BusinessPartner;
use App\Services\SapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UploadPartnerFilesToSapJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public BusinessPartner $partner)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sapService = new SapService();
        try {
            if (!empty($this->partner->tax_card)) {
                $sapService->uploadFile($this->partner->request_id . '#TAXCARD', $this->partner->tax_card);
            }
            if (!empty($this->partner->commercial_registration)) {
                $sapService->uploadFile($this->partner->request_id . '#COMCARD', $this->partner->commercial_registration);
            }
            Log::error('SAP File Upload Successfully for partner: ' . $this->partner->request_id);
        } catch (\Exception $e) {
            Log::error('SAP File Upload Failed: ' . $e->getMessage());
        }
    }
}
