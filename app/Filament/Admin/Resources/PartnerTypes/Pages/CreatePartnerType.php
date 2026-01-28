<?php

namespace App\Filament\Admin\Resources\PartnerTypes\Pages;

use App\Filament\Admin\Resources\PartnerTypes\PartnerTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePartnerType extends CreateRecord
{
    protected static string $resource = PartnerTypeResource::class;
}
