<?php

namespace App\Filament\Admin\Resources\Materials\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AreasRelationManager extends RelationManager
{
    protected static string $relationship = 'areas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_en')
            ->columns([
                Tables\Columns\TextColumn::make('name_en')->label(__('Name (EN)')),
                Tables\Columns\TextColumn::make('name_ar')->label(__('Name (AR)')),
                Tables\Columns\TextColumn::make('code')->label(__('Code')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }
}
