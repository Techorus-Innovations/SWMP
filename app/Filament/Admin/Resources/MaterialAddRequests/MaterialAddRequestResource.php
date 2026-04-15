<?php

namespace App\Filament\Admin\Resources\MaterialAddRequests;

use App\Filament\Admin\Resources\MaterialAddRequests\Pages\ListMaterialAddRequests;
use App\Filament\Admin\Resources\MaterialAddRequests\Tables\MaterialAddRequestsTable;
use App\Models\MaterialAddRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaterialAddRequestResource extends Resource
{
    protected static ?string $model = MaterialAddRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static UnitEnum|string|null $navigationGroup = 'Materials Management';

    protected static ?string $navigationLabel = 'Material Add Requests';

    protected static ?string $pluralModelLabel = 'Material Add Requests';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return MaterialAddRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterialAddRequests::route('/'),
        ];
    }
}
