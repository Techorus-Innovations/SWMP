<?php

namespace App\Filament\Admin\Resources\BusinessPartners;

use App\Filament\Admin\Resources\BusinessPartners\Pages\CreateBusinessPartner;
use App\Filament\Admin\Resources\BusinessPartners\Pages\EditBusinessPartner;
use App\Filament\Admin\Resources\BusinessPartners\Pages\ListBusinessPartners;
use App\Filament\Admin\Resources\BusinessPartners\Schemas\BusinessPartnerForm;
use App\Filament\Admin\Resources\BusinessPartners\Tables\BusinessPartnersTable;
use App\Models\BusinessPartner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BusinessPartnerResource extends Resource
{
    protected static ?string $model = BusinessPartner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BusinessPartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessPartnersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessPartners::route('/'),
            // 'create' => CreateBusinessPartner::route('/create'),
            // 'edit' => EditBusinessPartner::route('/{record}/edit'),
        ];
    }
}
