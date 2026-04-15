<?php

namespace App\Filament\Partner\Resources\Users;

use App\Filament\Partner\Resources\Users\Pages\CreateUser;
use App\Filament\Partner\Resources\Users\Pages\EditUser;
use App\Filament\Partner\Resources\Users\Pages\ListUsers;
use App\Filament\Partner\Resources\Users\Schemas\UserForm;
use App\Filament\Partner\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function canAccess(): bool
    {
        return filament()->auth()->user()->canManageUsers();
    }

    public static function getEloquentQuery(): Builder
    {
        $businessPartnerId = auth()->user()?->business_partner_id;

        return parent::getEloquentQuery()
            ->where('business_partner_id', $businessPartnerId)
            ->whereNot('id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
