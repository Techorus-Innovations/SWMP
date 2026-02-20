<?php

namespace App\Filament\Admin\Resources\Plants\Pages;

use App\Filament\Admin\Resources\Plants\PlantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlant extends ViewRecord
{
    protected static string $resource = PlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
