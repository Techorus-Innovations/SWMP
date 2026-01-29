<?php

namespace App\Filament\Admin\Resources\PartnerRoles\Pages;

use App\Filament\Admin\Resources\PartnerRoles\PartnerRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartnerRoles extends ListRecords
{
    protected static string $resource = PartnerRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
