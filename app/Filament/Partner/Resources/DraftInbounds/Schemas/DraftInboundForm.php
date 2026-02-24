<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DraftInboundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),

            TextInput::make('contract_id')
                ->label('Contract ID')
                ->maxLength(255),
        ]);
    }
}
