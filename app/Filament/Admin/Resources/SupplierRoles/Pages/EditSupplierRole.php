<?php

namespace App\Filament\Admin\Resources\SupplierRoles\Pages;

use App\Filament\Admin\Resources\SupplierRoles\SupplierRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierRole extends EditRecord
{
    protected static string $resource = SupplierRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
