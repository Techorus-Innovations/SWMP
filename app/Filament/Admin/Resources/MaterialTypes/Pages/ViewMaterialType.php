<?php

namespace App\Filament\Admin\Resources\MaterialTypes\Pages;

use App\Filament\Admin\Resources\MaterialTypes\MaterialTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialType extends ViewRecord
{
    protected static string $resource = MaterialTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
