<?php

namespace App\Filament\Partner\Resources\MaterialAddRequestResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MaterialAddRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('gtin')
                ->label('GTIN Code')
                ->required()
                ->maxLength(255),
        ]);
    }
}
