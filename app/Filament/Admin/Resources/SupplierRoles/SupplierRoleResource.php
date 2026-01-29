<?php

namespace App\Filament\Admin\Resources\SupplierRoles;

use App\Filament\Admin\Resources\SupplierRoles\Pages\CreateSupplierRole;
use App\Filament\Admin\Resources\SupplierRoles\Pages\EditSupplierRole;
use App\Filament\Admin\Resources\SupplierRoles\Pages\ListSupplierRoles;
use App\Filament\Admin\Resources\SupplierRoles\Schemas\SupplierRoleForm;
use App\Filament\Admin\Resources\SupplierRoles\Tables\SupplierRolesTable;
use App\Models\SupplierRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierRoleResource extends Resource
{
    protected static ?string $model = SupplierRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SupplierRoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierRolesTable::configure($table);
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
            'index' => ListSupplierRoles::route('/'),
            'create' => CreateSupplierRole::route('/create'),
            'edit' => EditSupplierRole::route('/{record}/edit'),
        ];
    }
}
