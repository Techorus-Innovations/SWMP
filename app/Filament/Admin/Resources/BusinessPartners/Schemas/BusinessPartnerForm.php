<?php

namespace App\Filament\Admin\Resources\BusinessPartners\Schemas;

use App\Enums\BusinessPartnerTypeEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BusinessPartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('location')
                    ->required(),
                TextInput::make('phone_number')
                    ->tel()
                    ->required(),
                Select::make('type')
                    ->label('Business Partner Type')
                    ->options(collect(BusinessPartnerTypeEnum::cases())->mapWithKeys(fn (BusinessPartnerTypeEnum $case) => [$case->value => $case->label()]))
                    ->required(),
            ]);
    }
}
