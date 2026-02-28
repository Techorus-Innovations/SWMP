<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Schemas;

use App\Models\Material;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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

            Repeater::make('materials')
                ->label('Materials')
                ->schema([
                    Select::make('material_id')
                        ->label('Material')
                        ->options(
                            Material::query()
                                ->get()
                                ->mapWithKeys(fn ($m) => [
                                    $m->id => (app()->getLocale() === 'ar' ? $m->name_ar : $m->name_en),
                                ])
                        )
                        ->searchable()
                        ->required()
                        ->distinct(),
                    TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                ])
                ->defaultItems(0)
                ->addActionLabel('Add Material')
                ->columnSpanFull(),
        ]);
    }
}
