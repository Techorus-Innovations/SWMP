<?php

namespace App\Filament\Admin\Resources\PlantAreas\Pages;

use App\Filament\Admin\Resources\PlantAreas\PlantAreaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlantArea extends EditRecord
{
    protected static string $resource = PlantAreaResource::class;
}
