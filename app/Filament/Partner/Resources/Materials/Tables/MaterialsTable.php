<?php

namespace App\Filament\Partner\Resources\Materials\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaterialsTable
{
    public static function configure(Table $table): Table
    {
        $isAr = app()->getLocale() === 'ar';
        $isEn = app()->getLocale() === 'en';

        return $table
            ->columns([
                TextColumn::make('name_en')
                    ->label(__('Name'))
                    ->visible($isEn)
                    ->searchable($isEn),
                TextColumn::make('name_ar')
                    ->label(__('Name'))
                    ->visible($isAr)
                    ->searchable( $isAr),

                TextColumn::make('code')
                    ->label(__('Code'))
                    ->searchable(),

                TextColumn::make('materialType.name_en')
                    ->label(__('Material Type'))
                    ->visible($isEn)
                    ->searchable($isEn),
                
                TextColumn::make('materialType.name_ar')
                    ->label(__('Material Type'))
                    ->visible($isAr)
                    ->searchable($isAr),
                
                TextColumn::make('materialGroup.name_en')
                    ->label(__('Material Group'))
                    ->visible($isEn)
                    ->searchable($isEn),

                TextColumn::make('materialGroup.name_ar')
                    ->label(__('Material Group'))
                    ->visible($isAr)
                    ->searchable($isAr),
                TextColumn::make('gtin_code')
                    ->label(__('GTIN Code'))
                    ->searchable(),
            ])
            ->filters([])
            ->recordActions([
            ])
            ->toolbarActions([]);
    }
}
