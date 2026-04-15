<?php

namespace App\Filament\Admin\Resources\MaterialAddRequests\Pages;

use App\Filament\Admin\Resources\MaterialAddRequests\MaterialAddRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListMaterialAddRequests extends ListRecords
{
    protected static string $resource = MaterialAddRequestResource::class;
}
