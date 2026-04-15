<?php

namespace App\Filament\Partner\Resources\MaterialAddRequestResource\Pages;

use App\Filament\Partner\Resources\MaterialAddRequestResource\MaterialAddRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialAddRequests extends ListRecords
{
    protected static string $resource = MaterialAddRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
