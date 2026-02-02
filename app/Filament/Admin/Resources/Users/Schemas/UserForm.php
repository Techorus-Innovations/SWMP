<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserTypeEnum;
use App\Models\Company;
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
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state)),
                Select::make('company_id')
                    ->label('Company')
                    ->options(Company::pluck('company_name', 'id'))
                    ->required()
                    ->disabled(fn (string $operation) => $operation === 'edit'),
                Select::make('type')
                    ->label('User Type')
                    ->options(collect(UserTypeEnum::cases())->mapWithKeys(fn (UserTypeEnum $case) => [$case->value => $case->label()]))
                    ->required()
                    ->disabled(fn (string $operation) => $operation === 'edit'),
            ]);
    }
}
