<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDraftInbounds extends ListRecords
{
    protected static string $resource = DraftInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
