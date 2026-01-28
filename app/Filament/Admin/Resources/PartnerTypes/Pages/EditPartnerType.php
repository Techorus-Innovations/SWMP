<?php

namespace App\Filament\Admin\Resources\PartnerTypes\Pages;

use App\Filament\Admin\Resources\PartnerTypes\PartnerTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartnerType extends EditRecord
{
    protected static string $resource = PartnerTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
