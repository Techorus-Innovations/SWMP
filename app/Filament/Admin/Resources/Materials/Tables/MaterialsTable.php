<?php

namespace App\Filament\Admin\Resources\Materials\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->where('sync_at', function ($subQuery) {
                    $subQuery->selectRaw('max(sync_at)')->from('materials');
                });
            })
            ->columns([
                TextColumn::make('name_en')
                    ->searchable(),
                TextColumn::make('name_ar')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('materialType.name_en')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('materialGroup.name_en')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gtin_code')
                    ->label(__('GTIN Code'))
                    ->searchable(),
                TextColumn::make('default_unit')
                    ->label(__('Default Unit'))
                    ->searchable(),
                TextColumn::make('pal_to_default_unit')
                    ->label(__('PAL to Default Unit'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
