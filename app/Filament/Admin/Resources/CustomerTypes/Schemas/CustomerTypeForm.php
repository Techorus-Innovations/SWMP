<?php

namespace App\Filament\Admin\Resources\CustomerTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('Code'))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name_en')
                    ->label(__('Name (English)'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_ar')
                    ->label(__('Name (Arabic)'))
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
