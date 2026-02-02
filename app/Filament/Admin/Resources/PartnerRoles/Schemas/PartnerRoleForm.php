<?php

namespace App\Filament\Admin\Resources\PartnerRoles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PartnerRoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('Description'))
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
