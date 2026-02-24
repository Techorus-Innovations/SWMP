<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DraftInboundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
            ->defaultSort('created_at', 'desc');
    }
}
