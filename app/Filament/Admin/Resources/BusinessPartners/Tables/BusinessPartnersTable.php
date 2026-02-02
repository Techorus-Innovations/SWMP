<?php

namespace App\Filament\Admin\Resources\BusinessPartners\Tables;

use App\Enums\BusinessPartnerTypeEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BusinessPartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('User'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('Business Partner Name'))
                    ->searchable(),
                TextColumn::make('location')
                    ->label(__('Location'))
                    ->searchable(),
                TextColumn::make('phone_number')
                    ->label(__('Phone Number'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('Business Partner Type'))
                    ->formatStateUsing(fn (BusinessPartnerTypeEnum $state): string => $state->label())
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
