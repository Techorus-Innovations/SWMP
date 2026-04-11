<?php

namespace App\Filament\Admin\Resources\Regions\Pages;

use App\Filament\Admin\Resources\Regions\RegionResource;
use App\Filament\Imports\RegionImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // ImportAction::make()
            //     ->importer(RegionImporter::class)
        ];
    }
}
