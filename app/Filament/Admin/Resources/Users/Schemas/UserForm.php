<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\Company;
use Filament\Forms\Components\DateTimePicker;
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
                    ->required(),
                Select::make('company_id')
                    ->label('Company')
                    ->options(Company::pluck('company_name', 'id'))
                    ->required(),
                TextInput::make('role')
                    ->required(),
            ]);
    }
}
