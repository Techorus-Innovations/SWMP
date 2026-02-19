<?php

namespace App\Filament\Imports;

use App\Models\MaterialType;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class MaterialTypeImporter extends Importer
{
    protected static ?string $model = MaterialType::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')
                ->requiredMapping()
                ->rules(['required', 'max:255', 'unique:material_types,code']),
            ImportColumn::make('name_en')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('name_ar')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): MaterialType
    {
        return new MaterialType();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your material type import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
