<?php

namespace App\Filament\Admin\Resources\BusinessPartners\Pages;

use App\Enums\BusinessPartnerTypeEnum;
use App\Filament\Admin\Resources\BusinessPartners\BusinessPartnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListBusinessPartners extends ListRecords
{
    protected static string $resource = BusinessPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            Tab::make(__('All'))
                ->query(fn ($query) => $query),
            Tab::make(__('Customer'))
                ->badge(fn ($count) => $count)
                ->query(fn ($query) => $query->where('type', BusinessPartnerTypeEnum::Customer->value)),
            Tab::make(__('Supplier'))
                ->badge(fn ($count) => $count)
                ->query(fn ($query) => $query->where('type', BusinessPartnerTypeEnum::Supplier->value)),
        ];
    }
}
