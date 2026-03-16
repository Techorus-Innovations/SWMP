<?php

namespace App\Filament\Partner\Resources\DraftOutbound\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DraftOutboundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ship_to_party')
                    ->label('Ship To Party')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
