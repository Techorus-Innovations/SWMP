<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDraftInbound extends EditRecord
{
    protected static string $resource = DraftInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
