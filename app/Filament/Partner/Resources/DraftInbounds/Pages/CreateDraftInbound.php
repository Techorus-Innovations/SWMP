<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDraftInbound extends CreateRecord
{
    protected static string $resource = DraftInboundResource::class;

    private array $materialsToSync = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->materialsToSync = collect($data['materials'] ?? [])
            ->pluck('material_id')
            ->filter()
            ->unique()
            ->toArray();

        unset($data['materials']);

        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->materials()->sync($this->materialsToSync);
    }
}
