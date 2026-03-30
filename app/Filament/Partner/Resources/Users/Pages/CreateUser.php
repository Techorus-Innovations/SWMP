<?php

namespace App\Filament\Partner\Resources\Users\Pages;

use App\Filament\Partner\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['business_partner_id'] = auth()->user()?->business_partner_id;
        $data['type'] = auth()->user()?->type;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole('partner');
    }
}
