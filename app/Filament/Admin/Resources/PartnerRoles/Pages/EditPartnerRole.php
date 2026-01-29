<?php

namespace App\Filament\Admin\Resources\PartnerRoles\Pages;

use App\Filament\Admin\Resources\PartnerRoles\PartnerRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartnerRole extends EditRecord
{
    protected static string $resource = PartnerRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
