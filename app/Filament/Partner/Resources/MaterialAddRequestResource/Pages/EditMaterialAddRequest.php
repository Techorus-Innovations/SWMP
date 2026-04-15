<?php

namespace App\Filament\Partner\Resources\MaterialAddRequestResource\Pages;

use App\Filament\Partner\Resources\MaterialAddRequestResource\MaterialAddRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialAddRequest extends EditRecord
{
    protected static string $resource = MaterialAddRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
