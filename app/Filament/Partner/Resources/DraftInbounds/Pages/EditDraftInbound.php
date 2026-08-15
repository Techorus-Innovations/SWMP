<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditDraftInbound extends EditRecord
{
    protected static string $resource = DraftInboundResource::class;

    #[On('materials-updated')]
    public function refreshMaterials(): void
    {
        $this->record->refresh();
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (filled($this->record->attp_file)) {
            Notification::make()
                ->title('Cannot Edit')
                ->body('Draft inbound cannot be edited once ATTP file has been submitted.')
                ->warning()
                ->send();

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

