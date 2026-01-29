<?php

namespace App\Filament\Admin\Resources\SupplierRoles\Pages;

use App\Filament\Admin\Resources\SupplierRoles\SupplierRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierRoles extends ListRecords
{
    protected static string $resource = SupplierRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
