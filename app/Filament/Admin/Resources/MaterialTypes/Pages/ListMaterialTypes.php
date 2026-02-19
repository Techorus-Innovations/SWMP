<?php

namespace App\Filament\Admin\Resources\MaterialTypes\Pages;

use App\Filament\Admin\Resources\MaterialTypes\MaterialTypeResource;
use App\Filament\Imports\MaterialTypeImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialTypes extends ListRecords
{
    protected static string $resource = MaterialTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->importer(MaterialTypeImporter::class)
        ];
    }
}
