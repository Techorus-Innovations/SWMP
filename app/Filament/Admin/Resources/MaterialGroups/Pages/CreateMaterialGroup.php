<?php

namespace App\Filament\Admin\Resources\MaterialGroups\Pages;

use App\Filament\Admin\Resources\MaterialGroups\MaterialGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterialGroup extends CreateRecord
{
    protected static string $resource = MaterialGroupResource::class;
}
