<?php

namespace App\Filament\Admin\Resources\BusinessPartners\Pages;

use App\Filament\Admin\Resources\BusinessPartners\BusinessPartnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusinessPartner extends EditRecord
{
    protected static string $resource = BusinessPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
