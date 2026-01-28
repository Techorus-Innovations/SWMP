<?php

namespace App\Filament\Admin\Resources\CustomerTypes;

use App\Filament\Admin\Resources\CustomerTypes\Pages\CreateCustomerType;
use App\Filament\Admin\Resources\CustomerTypes\Pages\EditCustomerType;
use App\Filament\Admin\Resources\CustomerTypes\Pages\ListCustomerTypes;
use App\Filament\Admin\Resources\CustomerTypes\Schemas\CustomerTypeForm;
use App\Filament\Admin\Resources\CustomerTypes\Tables\CustomerTypesTable;
use App\Models\CustomerType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerTypeResource extends Resource
{
    protected static ?string $model = CustomerType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return CustomerTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerTypesTable::configure($table);
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
            'index' => ListCustomerTypes::route('/'),
            'create' => CreateCustomerType::route('/create'),
            'edit' => EditCustomerType::route('/{record}/edit'),
        ];
    }
}
