<?php

namespace App\Filament\Partner\Resources\MaterialAddRequestResource;

use App\Filament\Partner\Resources\MaterialAddRequestResource\Pages\CreateMaterialAddRequest;
use App\Filament\Partner\Resources\MaterialAddRequestResource\Pages\EditMaterialAddRequest;
use App\Filament\Partner\Resources\MaterialAddRequestResource\Pages\ListMaterialAddRequests;
use App\Filament\Partner\Resources\MaterialAddRequestResource\Schemas\MaterialAddRequestForm;
use App\Filament\Partner\Resources\MaterialAddRequestResource\Tables\MaterialAddRequestsTable;
use App\Models\MaterialAddRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaterialAddRequestResource extends Resource
{
    protected static ?string $model = MaterialAddRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentPlus;

    protected static UnitEnum|string|null $navigationGroup = 'Materials';

    protected static ?string $navigationLabel = 'Add Material Request';

    protected static ?string $pluralModelLabel = 'Material Add Requests';

    public static function canEdit($record): bool
    {
        return $record->status === 'pending';
    }

    public static function canDelete($record): bool
    {
        return $record->status === 'pending';
    }

    public static function form(Schema $schema): Schema
    {
        return MaterialAddRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialAddRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterialAddRequests::route('/'),
            'create' => CreateMaterialAddRequest::route('/create'),
            'edit' => EditMaterialAddRequest::route('/{record}/edit'),
        ];
    }
}
