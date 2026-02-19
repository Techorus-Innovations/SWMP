<?php

namespace App\Filament\Admin\Resources\PlantAreas\Pages;

use App\Filament\Admin\Resources\PlantAreas\PlantAreaResource;
use App\Filament\Imports\PlantAreaImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListPlantAreas extends ListRecords
{
    protected static string $resource = PlantAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->importer(PlantAreaImporter::class)
        ];
    }
}
