<?php

namespace App\Filament\Admin\Resources\PartnerTypes\Tables;

use App\Enums\PartnerTypeEnum;
use App\Enums\TaxCategoryEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartnerTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grouping_code')
                    ->label('Grouping Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_en')
                    ->label('Name (English)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_ar')
                    ->label('Name (Arabic)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (?PartnerTypeEnum $state) => $state?->label() ?? '-')
                    ->sortable(),
                TextColumn::make('customerType.name_en')
                    ->label('Customer Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tax_cat')
                    ->label('Tax Category')
                    ->formatStateUsing(fn (?TaxCategoryEnum $state) => $state?->label() ?? '-')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
