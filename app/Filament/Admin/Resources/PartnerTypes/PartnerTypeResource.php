<?php

namespace App\Filament\Admin\Resources\PartnerTypes;

use App\Filament\Admin\Resources\PartnerTypes\Pages\CreatePartnerType;
use App\Filament\Admin\Resources\PartnerTypes\Pages\EditPartnerType;
use App\Filament\Admin\Resources\PartnerTypes\Pages\ListPartnerTypes;
use App\Filament\Admin\Resources\PartnerTypes\Schemas\PartnerTypeForm;
use App\Filament\Admin\Resources\PartnerTypes\Tables\PartnerTypesTable;
use App\Models\PartnerType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PartnerTypeResource extends Resource
{
    protected static ?string $model = PartnerType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return PartnerTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnerTypesTable::configure($table);
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
            'index' => ListPartnerTypes::route('/'),
            'create' => CreatePartnerType::route('/create'),
            'edit' => EditPartnerType::route('/{record}/edit'),
        ];
    }
}
