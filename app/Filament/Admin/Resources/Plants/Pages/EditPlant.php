<?php

namespace App\Filament\Admin\Resources\Plants\Pages;

use App\Filament\Admin\Resources\Plants\PlantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlant extends EditRecord
{
    protected static string $resource = PlantResource::class;
}
