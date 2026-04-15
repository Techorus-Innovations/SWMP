<?php

namespace App\Filament\Admin\Resources\PlantAreas;

use App\Filament\Admin\Resources\PlantAreas\Pages\CreatePlantArea;
use App\Filament\Admin\Resources\PlantAreas\Pages\EditPlantArea;
use App\Filament\Admin\Resources\PlantAreas\Pages\ListPlantAreas;
use App\Filament\Admin\Resources\PlantAreas\Schemas\PlantAreaForm;
use App\Filament\Admin\Resources\PlantAreas\Tables\PlantAreasTable;
use App\Filament\Admin\Resources\PlantAreas\Pages\ViewPlantArea;
use App\Filament\Admin\Resources\PlantAreas\RelationManagers\MaterialsRelationManager;
use App\Models\PlantArea;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PlantAreaResource extends Resource
{
    protected static ?string $model = PlantArea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static UnitEnum|string|null $navigationGroup = 'Materials Management';

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return PlantAreaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlantAreasTable::configure($table);
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
            'index' => ListPlantAreas::route('/'),
            'create' => CreatePlantArea::route('/create'),
            'view' => ViewPlantArea::route('/{record}'),
            'edit' => EditPlantArea::route('/{record}/edit'),
        ];
    }
}
