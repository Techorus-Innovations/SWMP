<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDraftInbound extends EditRecord
{
    protected static string $resource = DraftInboundResource::class;

    private array $materialsToSync = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['materials'] = $this->record->materials
            ->map(fn ($material) => ['material_id' => $material->id])
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->materialsToSync = collect($data['materials'] ?? [])
            ->pluck('material_id')
            ->filter()
            ->unique()
            ->toArray();

        unset($data['materials']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->materials()->sync($this->materialsToSync);
    }
}
