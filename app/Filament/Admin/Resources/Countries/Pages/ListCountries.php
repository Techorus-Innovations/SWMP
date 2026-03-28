<?php

namespace App\Filament\Admin\Resources\Countries\Pages;

use App\Filament\Admin\Resources\Countries\CountryResource;
use App\Filament\Imports\CountryImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListCountries extends ListRecords
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // ImportAction::make()
            //     ->importer(CountryImporter::class)
        ];
    }
}
