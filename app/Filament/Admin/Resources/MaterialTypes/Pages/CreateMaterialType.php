<?php

namespace App\Filament\Admin\Resources\MaterialTypes\Pages;

use App\Filament\Admin\Resources\MaterialTypes\MaterialTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterialType extends CreateRecord
{
    protected static string $resource = MaterialTypeResource::class;
}
