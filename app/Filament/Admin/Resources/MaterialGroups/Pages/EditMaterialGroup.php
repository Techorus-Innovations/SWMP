<?php

namespace App\Filament\Admin\Resources\MaterialGroups\Pages;

use App\Filament\Admin\Resources\MaterialGroups\MaterialGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialGroup extends EditRecord
{
    protected static string $resource = MaterialGroupResource::class;
}
