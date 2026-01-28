<?php

namespace App\Filament\Admin\Resources\CustomerTypes\Pages;

use App\Filament\Admin\Resources\CustomerTypes\CustomerTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerTypes extends ListRecords
{
    protected static string $resource = CustomerTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
