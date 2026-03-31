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
            ->map(fn ($material) => [
                'material_id' => $material->id,
                'quantity' => $material->pivot->quantity,
            ])
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $syncData = collect($data['materials'] ?? [])
            ->pluck('quantity', 'material_id')
            ->filter()
            ->toArray();
        $this->materialsToSync = [];
        foreach ($syncData as $materialId => $quantity) {
            $this->materialsToSync[$materialId] = [
                'quantity' => $quantity,
            ];
        }
        // dd($this->materialsToSync);

        unset($data['materials']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->materials()->sync($this->materialsToSync);
    }
}
