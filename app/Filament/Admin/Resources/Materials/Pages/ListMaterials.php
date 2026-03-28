<?php

namespace App\Filament\Admin\Resources\Materials\Pages;

use App\Filament\Admin\Resources\Materials\MaterialResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListMaterials extends ListRecords
{
    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
            Action::make('runCommand')
            ->label('Run Sync Materials')
            ->icon('heroicon-m-play')
            ->color('primary')
            ->requiresConfirmation()
            ->action(function () {
                Artisan::call('materials:sync');

                // Notify the user on completion
                Notification::make()
                    ->title('Materials Synced successfully')
                    ->success()
                    ->send();
            }),
        ];
    }
}
