<?php

namespace App\Filament\Partner\Resources\MaterialAddRequestResource\Pages;

use App\Filament\Partner\Resources\MaterialAddRequestResource\MaterialAddRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterialAddRequest extends CreateRecord
{
    protected static string $resource = MaterialAddRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
