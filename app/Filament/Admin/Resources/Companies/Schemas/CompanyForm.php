<?php

namespace App\Filament\Admin\Resources\Companies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('company_name')
                    ->required(),
                TextInput::make('location')
                    ->required(),
                TextInput::make('phone_number')
                    ->tel()
                    ->required(),
            ]);
    }
}
