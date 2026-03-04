<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Harris21\Fuse\CircuitBreaker;

class DraftInboundsTable
{
    public static function configure(Table $table): Table
    {
        $circuitBreaker = new CircuitBreaker('sap');
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('user_id', auth()->id()))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contract_id')
                    ->label('Contract ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('materials_count')
                    ->label('Materials')
                    ->counts('materials')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make()
                    ->disabled(fn() => $circuitBreaker->isOpen())
                    ->tooltip(fn () => $circuitBreaker->isOpen() ? 'External service is currently unavailable' : null),
                EditAction::make()
                    ->disabled(fn() => $circuitBreaker->isOpen())
                    ->tooltip(fn () => $circuitBreaker->isOpen() ? 'External service is currently unavailable' : null),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
