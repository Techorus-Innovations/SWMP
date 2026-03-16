<?php

namespace App\Filament\Partner\Resources\DraftOutbound\Pages;

use App\Filament\Partner\Resources\DraftOutbound\DraftOutboundResource;
use App\Models\DraftOutbound;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDraftOutbound extends CreateRecord
{
    protected static string $resource = DraftOutboundResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['business_partner_id'] = filament()->auth()->user()->business_partner_id;
        return $data;
    }
}
