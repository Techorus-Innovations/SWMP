<?php

namespace App\Filament\Partner\Resources\DraftInbounds\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    protected static ?string $title = 'Materials';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(app()->getLocale() === 'ar' ? 'name_ar' : 'name_en')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_en')
                    ->label('Name (EN)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_ar')
                    ->label('Name (AR)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('materialType.name_en')
                    ->label('Type')
                    ->sortable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name_en', 'name_ar', 'code']),
            ])
            ->actions([
                DetachAction::make(),
            ]);
    }
}
