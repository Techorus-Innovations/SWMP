<?php

namespace App\Filament\Admin\Resources\WorkingHours\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkingHoursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('day')
                    ->searchable(),
                TextColumn::make('start')->formatStateUsing(fn ($state) => date('h:i A', strtotime($state))),
                TextColumn::make('end')->formatStateUsing(fn ($state) => date('h:i A', strtotime($state))),
                TextColumn::make('quantity')
                    ->label(__('Max Requests Per Slot Per Hour')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
