<?php

namespace App\Filament\Partner\Resources\DraftOutbound\Pages;

use App\Filament\Partner\Resources\DraftOutbound\DraftOutboundResource;
use App\Models\DraftOutbound;
use Filament\Resources\Pages\ListRecords;

class ListDraftOutbounds extends ListRecords
{
    protected static string $resource = DraftOutboundResource::class;

    protected function getHeaderActions(): array
    {
        // Check if user already has an existing draft
        $existingDraft = DraftOutbound::where('business_partner_id', filament()->auth()->user()->business_partner_id)
            ->exists();

        $actions = [];

        if (!$existingDraft) {
            $actions[] = \Filament\Actions\CreateAction::make()
                ->createAnother(false);
        }

        return $actions;
    }
}
