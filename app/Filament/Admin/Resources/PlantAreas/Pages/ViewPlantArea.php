<?php

namespace App\Filament\Admin\Resources\PlantAreas\Pages;

use App\Filament\Admin\Resources\PlantAreas\PlantAreaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlantArea extends ViewRecord
{
    protected static string $resource = PlantAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
