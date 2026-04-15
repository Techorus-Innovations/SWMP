<?php

namespace App\Filament\Admin\Resources\MaterialGroups\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

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
