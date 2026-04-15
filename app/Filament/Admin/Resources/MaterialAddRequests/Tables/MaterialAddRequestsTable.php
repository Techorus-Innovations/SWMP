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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('gtin')
                    ->label('GTIN')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('user.name')
                    ->label('Requested By')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'accepted']);
                        Notification::make()
                            ->title('Request accepted')
                            ->success()
                            ->send();
                    })
                    ->hidden(fn ($record) => $record->status !== 'pending'),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Request rejected')
                            ->danger()
                            ->send();
                    })
                    ->hidden(fn ($record) => $record->status !== 'pending'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
