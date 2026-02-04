<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserTypeEnum;
use App\Models\BusinessPartner;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('Email address'))
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state)),
                Select::make('business_partner_id')
                    ->label(__('Business Partner'))
                    ->options(BusinessPartner::pluck('name1', 'id'))
                    ->required()
                    ->disabled(fn (string $operation) => $operation === 'edit'),
                Select::make('type')
                    ->label(__('User Type'))
                    ->options(collect(UserTypeEnum::cases())->mapWithKeys(fn (UserTypeEnum $case) => [$case->value => $case->label()]))
                    ->required()
                    ->disabled(fn (string $operation) => $operation === 'edit'),
            ]);
    }
}
