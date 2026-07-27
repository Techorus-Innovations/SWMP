<?php

namespace App\Filament\Partner\Resources\Materials\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialsTable
{
    public static function configure(Table $table): Table
    {
        $isAr = app()->getLocale() === 'ar';
        $isEn = app()->getLocale() === 'en';

        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->where('sync_at', function ($subQuery) {
                    $subQuery->selectRaw('max(sync_at)')->from('materials');
                });
            })
            ->columns([
                TextColumn::make('code')
                    ->label(__('Code'))
                    ->searchable(),

                TextColumn::make('name_en')
                    ->label(__('Name EN'))
                    // ->visible($isEn)
                    // ->searchable($isEn),
                    ->searchable(),
                TextColumn::make('name_ar')
                    ->label(__('Name AR'))
                    // ->visible($isAr)
                    // ->searchable($isAr),
                    ->searchable(),

                TextColumn::make('gtin_code')
                    ->label(__('GTIN Code'))
                    ->searchable(),

                TextColumn::make('default_unit')
                    ->label(__('Default Unit'))
                    ->searchable(),

                // TextColumn::make('pal_to_default_unit')
                //     ->label(__('PAL to Default Unit'))
                //     ->searchable(),

                // TextColumn::make('materialType.name_en')
                //     ->label(__('Material Type'))
                //     ->visible($isEn)
                //     ->searchable($isEn),

                // TextColumn::make('materialType.name_ar')
                //     ->label(__('Material Type'))
                //     ->visible($isAr)
                //     ->searchable($isAr),

                TextColumn::make('materialGroup.name_en')
                    ->label(__('Material Group'))
                    ->visible($isEn)
                    ->searchable($isEn),

                TextColumn::make('materialGroup.name_ar')
                    ->label(__('Material Group'))
                    ->visible($isAr)
                    ->searchable($isAr),
            ])
            ->filters([])
            ->recordActions([
            ])
            ->toolbarActions([]);
    }
}
