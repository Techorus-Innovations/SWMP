<?php

namespace App\Filament\Admin\Resources\MaterialGroups\Pages;

use App\Filament\Admin\Resources\MaterialGroups\MaterialGroupResource;
use App\Filament\Imports\MaterialGroupImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialGroups extends ListRecords
{
    protected static string $resource = MaterialGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
            // ImportAction::make()
            //     ->importer(MaterialGroupImporter::class)
        ];
    }
}
