<?php

namespace App\Filament\Partner\Resources\Materials\Pages;

use App\Filament\Partner\Resources\Materials\MaterialResource;
use Filament\Resources\Pages\ListRecords;

class ListMaterials extends ListRecords
{
    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
