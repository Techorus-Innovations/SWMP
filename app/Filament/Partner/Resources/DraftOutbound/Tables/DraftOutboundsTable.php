<?php

namespace App\Filament\Partner\Resources\DraftOutbound\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DraftOutboundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query->where('business_partner_id', auth()->user()->business_partner_id);
            })
            ->columns([
                TextColumn::make('shipping_type')
                    ->label(__('Shipping Type'))
                    ->state(fn ($record) => $record->ship_to_party === filament()->auth()->user()->businessPartner->bp_code
                        ? __('Receive from Warehouse')
                        : '—'
                    ),
                TextColumn::make('ship_to_party')
                    ->label(__('Ship To Party'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('Created At'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
