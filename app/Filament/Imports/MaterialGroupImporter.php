<?php

namespace App\Filament\Imports;

use App\Models\MaterialGroup;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class MaterialGroupImporter extends Importer
{
    protected static ?string $model = MaterialGroup::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')
                ->requiredMapping()
                ->rules(['required', 'max:255', 'unique:material_groups,code']),
            ImportColumn::make('name_en')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('name_ar')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): MaterialGroup
    {
        return new MaterialGroup();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your material group import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
