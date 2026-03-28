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
                    $exitCode = Artisan::call('materials:sync');

                    if ($exitCode === 0) {
                        Notification::make()
                            ->title('Materials synced successfully')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Sync failed')
                            ->body('Materials sync failed')
                            ->danger()
                            ->persistent() // Keeps the error visible until dismissed
                            ->send();
                    }
                }),
        ];
    }
}
