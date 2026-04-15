<?php

namespace App\Filament\Admin\Resources\Plants;

use App\Filament\Admin\Resources\Plants\Pages\CreatePlant;
use App\Filament\Admin\Resources\Plants\Pages\EditPlant;
use App\Filament\Admin\Resources\Plants\Pages\ListPlants;
use App\Filament\Admin\Resources\Plants\Schemas\PlantForm;
use App\Filament\Admin\Resources\Plants\Tables\PlantsTable;
use App\Filament\Admin\Resources\Plants\Pages\ViewPlant;
use App\Filament\Admin\Resources\Plants\RelationManagers\AreasRelationManager;
use App\Models\Plant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PlantResource extends Resource
{
    protected static ?string $model = Plant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static UnitEnum|string|null $navigationGroup = 'Materials Management';

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return PlantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlantsTable::configure($table);
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
            'index' => ListPlants::route('/'),
            'create' => CreatePlant::route('/create'),
            'view' => ViewPlant::route('/{record}'),
            'edit' => EditPlant::route('/{record}/edit'),
        ];
    }
}
