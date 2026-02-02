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
                    ->label(__('User'))
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label(__('Business Partner Name'))
                    ->required(),
                TextInput::make('location')
                    ->label(__('Location'))
                    ->required(),
                TextInput::make('phone_number')
                    ->label(__('Phone Number'))
                    ->tel()
                    ->required(),
                Select::make('type')
                    ->label('Business Partner Type')
                    ->options(collect(BusinessPartnerTypeEnum::cases())->mapWithKeys(fn (BusinessPartnerTypeEnum $case) => [$case->value => $case->label()]))
                    ->required(),
            ]);
    }
}
