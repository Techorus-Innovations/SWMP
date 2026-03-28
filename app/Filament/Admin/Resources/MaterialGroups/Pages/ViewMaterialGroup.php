<?php

namespace App\Filament\Admin\Resources\MaterialGroups\Pages;

use App\Filament\Admin\Resources\MaterialGroups\MaterialGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialGroup extends ViewRecord
{
    protected static string $resource = MaterialGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
