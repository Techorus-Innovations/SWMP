<?php

namespace App\Filament\Admin\Resources\PartnerTypes\Tables;

use App\Enums\BusinessPartnerTypeEnum;
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
                    ->label(__('Grouping Code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_en')
                    ->label(__('Name (English)'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_ar')
                    ->label(__('Name (Arabic)'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('Type'))
                    ->formatStateUsing(fn (?BusinessPartnerTypeEnum $state) => $state?->label() ?? '-')
                    ->sortable(),
                TextColumn::make('customerType.name_en')
                    ->label(__('Customer Type'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tax_cat')
                    ->label(__('Tax Category'))
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
