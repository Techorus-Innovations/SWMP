<?php

namespace App\Filament\Admin\Resources\CustomerTypes\Pages;

use App\Filament\Admin\Resources\CustomerTypes\CustomerTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerType extends CreateRecord
{
    protected static string $resource = CustomerTypeResource::class;
}
