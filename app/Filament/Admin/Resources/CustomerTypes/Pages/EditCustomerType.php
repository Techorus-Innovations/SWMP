<?php

namespace App\Filament\Admin\Resources\CustomerTypes\Pages;

use App\Filament\Admin\Resources\CustomerTypes\CustomerTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerType extends EditRecord
{
    protected static string $resource = CustomerTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
