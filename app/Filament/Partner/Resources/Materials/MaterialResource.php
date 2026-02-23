<?php

namespace App\Filament\Partner\Resources\Materials;

use App\Filament\Partner\Resources\Materials\Pages\ListMaterials;
use App\Filament\Partner\Resources\Materials\Pages\ViewMaterial;
use App\Filament\Partner\Resources\Materials\Schemas\MaterialInfolist;
use App\Filament\Partner\Resources\Materials\Tables\MaterialsTable;
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

    protected static UnitEnum|string|null $navigationGroup = 'Materials';

    public static function getRecordTitleAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
    }

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
        return MaterialsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterials::route('/')
        ];
    }
}
