<?php

namespace App\Filament\Admin\Resources\WorkingHours\Pages;

use App\Filament\Admin\Resources\WorkingHours\WorkingHoursResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkingHours extends ListRecords
{
    protected static string $resource = WorkingHoursResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
