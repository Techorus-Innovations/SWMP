<?php

namespace App\Filament\Admin\Resources\MaterialAddRequests\Tables;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaterialAddRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('gtin')
                    ->label(__('GTIN Code'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('user.name')
                    ->label(__('Requested By'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label(__('Accept'))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'accepted']);
                        Notification::make()
                            ->title(__('Request accepted'))
                            ->success()
                            ->send();
                    })
                    ->hidden(fn ($record) => $record->status !== 'pending'),
                Action::make('reject')
                    ->label(__('Reject'))
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title(__('Request rejected'))
                            ->danger()
                            ->send();
                    })
                    ->hidden(fn ($record) => $record->status !== 'pending'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
