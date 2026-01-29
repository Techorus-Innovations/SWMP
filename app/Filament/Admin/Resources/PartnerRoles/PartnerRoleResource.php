<?php

namespace App\Filament\Admin\Resources\PartnerRoles;

use App\Filament\Admin\Resources\PartnerRoles\Pages\CreatePartnerRole;
use App\Filament\Admin\Resources\PartnerRoles\Pages\EditPartnerRole;
use App\Filament\Admin\Resources\PartnerRoles\Pages\ListPartnerRoles;
use App\Filament\Admin\Resources\PartnerRoles\Schemas\PartnerRoleForm;
use App\Filament\Admin\Resources\PartnerRoles\Tables\PartnerRolesTable;
use App\Models\PartnerRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PartnerRoleResource extends Resource
{
    protected static ?string $model = PartnerRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PartnerRoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnerRolesTable::configure($table);
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
            'index' => ListPartnerRoles::route('/'),
            'create' => CreatePartnerRole::route('/create'),
            'edit' => EditPartnerRole::route('/{record}/edit'),
        ];
    }
}
