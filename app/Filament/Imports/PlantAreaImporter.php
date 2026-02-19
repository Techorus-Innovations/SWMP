<?php

namespace App\Filament\Imports;

use App\Models\PlantArea;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class PlantAreaImporter extends Importer
{
    protected static ?string $model = PlantArea::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')
                ->requiredMapping()
                ->rules(['required', 'max:255', 'unique:plant_areas,code']),
            ImportColumn::make('name_en')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('name_ar')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('plant_id')
                ->requiredMapping()
                ->rules(['required', 'exists:plants,id']),
        ];
    }

    public function resolveRecord(): PlantArea
    {
        return new PlantArea();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your plant area import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
