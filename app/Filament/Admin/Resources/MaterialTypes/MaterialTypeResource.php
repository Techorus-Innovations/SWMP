<?php

namespace App\Filament\Admin\Resources\MaterialTypes;

use App\Filament\Admin\Resources\MaterialTypes\Pages\CreateMaterialType;
use App\Filament\Admin\Resources\MaterialTypes\Pages\EditMaterialType;
use App\Filament\Admin\Resources\MaterialTypes\Pages\ListMaterialTypes;
use App\Filament\Admin\Resources\MaterialTypes\Schemas\MaterialTypeForm;
use App\Filament\Admin\Resources\MaterialTypes\Tables\MaterialTypesTable;
use App\Models\MaterialType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaterialTypeResource extends Resource
{
    protected static ?string $model = MaterialType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Materials Management';

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return MaterialTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterialTypes::route('/'),
            'create' => CreateMaterialType::route('/create'),
            'edit' => EditMaterialType::route('/{record}/edit'),
        ];
    }
}
