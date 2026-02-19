<?php

namespace App\Filament\Admin\Resources\MaterialTypes\Pages;

use App\Filament\Admin\Resources\MaterialTypes\MaterialTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialType extends EditRecord
{
    protected static string $resource = MaterialTypeResource::class;
}
