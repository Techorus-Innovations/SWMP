<?php

namespace App\Filament\Admin\Resources\PlantAreas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlantAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')
                    ->required(),
                TextInput::make('name_ar')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('plant_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}
