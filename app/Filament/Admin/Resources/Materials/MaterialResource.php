<?php

namespace App\Filament\Admin\Resources\Materials;

use App\Filament\Admin\Resources\Materials\Pages\ListMaterials;
use App\Filament\Admin\Resources\Materials\Schemas\MaterialForm;
use App\Filament\Admin\Resources\Materials\Tables\MaterialsTable;
use App\Filament\Admin\Resources\Materials\Pages\ViewMaterial;
use App\Filament\Admin\Resources\Materials\RelationManagers\AreasRelationManager;
use App\Models\Material;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Materials Management';

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function table(Table $table): Table
    {
        return MaterialsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AreasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterials::route('/'),
            'view' => ViewMaterial::route('/{record}'),
        ];
    }
}
