<?php

namespace App\Filament\Admin\Resources\Regions\Schemas;

use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('Code'))
                    ->required()
                    ->maxLength(3),
                TextInput::make('name_en')
                    ->label(__('Name (English)'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_ar')
                    ->label(__('Name (Arabic)'))
                    ->required()
                    ->maxLength(255),
                Select::make('country_id')
                    ->label(__('Country'))
                    ->options(Country::pluck('name_en', 'id'))
                    ->required(),
            ]);
    }
}
