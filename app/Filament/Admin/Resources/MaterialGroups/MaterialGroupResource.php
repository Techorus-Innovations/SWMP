<?php

namespace App\Filament\Admin\Resources\MaterialGroups;

use App\Filament\Admin\Resources\MaterialGroups\Pages\CreateMaterialGroup;
use App\Filament\Admin\Resources\MaterialGroups\Pages\EditMaterialGroup;
use App\Filament\Admin\Resources\MaterialGroups\Pages\ListMaterialGroups;
use App\Filament\Admin\Resources\MaterialGroups\Schemas\MaterialGroupForm;
use App\Filament\Admin\Resources\MaterialGroups\Tables\MaterialGroupsTable;
use App\Filament\Admin\Resources\MaterialGroups\Pages\ViewMaterialGroup;
use App\Filament\Admin\Resources\MaterialGroups\RelationManagers\MaterialsRelationManager;
use App\Models\MaterialGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaterialGroupResource extends Resource
{
    protected static ?string $model = MaterialGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static UnitEnum|string|null $navigationGroup = 'Materials Management';

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return MaterialGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MaterialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterialGroups::route('/'),
            'create' => CreateMaterialGroup::route('/create'),
            'view' => ViewMaterialGroup::route('/{record}'),
            'edit' => EditMaterialGroup::route('/{record}/edit'),
        ];
    }
}
