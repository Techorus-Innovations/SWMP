<?php

namespace App\Filament\Admin\Resources\MaterialGroups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MaterialGroupForm
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
            ]);
    }
}
