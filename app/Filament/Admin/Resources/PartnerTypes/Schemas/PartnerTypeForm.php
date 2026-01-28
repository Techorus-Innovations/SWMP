<?php

namespace App\Filament\Admin\Resources\PartnerTypes\Schemas;

use App\Enums\PartnerTypeEnum;
use App\Enums\TaxCategoryEnum;
use App\Models\CustomerType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PartnerTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('grouping_code')
                    ->label('Grouping Code')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_en')
                    ->label('Name (English)')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_ar')
                    ->label('Name (Arabic)')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Type')
                    ->options(collect(PartnerTypeEnum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->required(),
                Select::make('cust_type_id')
                    ->label('Customer Type')
                    ->options(CustomerType::pluck('name_en', 'id'))
                    ->nullable(),
                Select::make('tax_cat')
                    ->label('Tax Category')
                    ->options(collect(TaxCategoryEnum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->nullable(),
            ]);
    }
}
