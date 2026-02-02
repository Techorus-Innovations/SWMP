<?php

namespace App\Filament\Admin\Resources\BusinessPartners\Pages;

use App\Filament\Admin\Resources\BusinessPartners\BusinessPartnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessPartners extends ListRecords
{
    protected static string $resource = BusinessPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
