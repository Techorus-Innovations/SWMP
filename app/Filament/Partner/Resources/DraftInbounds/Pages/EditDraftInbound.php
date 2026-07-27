<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDraftInbound extends EditRecord
{
    protected static string $resource = DraftInboundResource::class;

    private array $materialsToSync = [];

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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['materials'] = $this->record->materials
            ->map(fn ($material) => [
                'material_id' => $material->id,
                'plant_area_id' => $material->pivot->plant_area_id,
                'quantity' => $material->pivot->quantity,
                'batch' => $material->pivot->batch,
                'manufacture_date' => $material->pivot->manufacture_date,
                'expiration_date' => $material->pivot->expiration_date,
            ])
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->materialsToSync = [];
        foreach ($data['materials'] ?? [] as $item) {
            if (! empty($item['material_id'])) {
                $this->materialsToSync[] = [
                    'material_id' => $item['material_id'],
                    'pivot_data' => [
                        'quantity' => $item['quantity'] ?? null,
                        'plant_area_id' => $item['plant_area_id'] ?? null,
                        'batch' => $item['batch'] ?? null,
                        'manufacture_date' => $item['manufacture_date'] ?? null,
                        'expiration_date' => $item['expiration_date'] ?? null,
                    ],
                ];
            }
        }

        unset($data['materials']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->materials()->detach();
        foreach ($this->materialsToSync as $item) {
            $this->record->materials()->attach($item['material_id'], $item['pivot_data']);
        }
    }
}
