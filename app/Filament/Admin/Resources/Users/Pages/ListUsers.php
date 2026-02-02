<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Enums\UserTypeEnum;
use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            Tab::make('All')
                    ->query(fn ($query) => $query),
                Tab::make('Admin')
                    ->badge(fn ($count) => $count)
                    ->query(fn ($query) => $query->where('type', UserTypeEnum::Admin->value)),
                Tab::make('Customer')
                    ->badge(fn ($count) => $count)
                    ->query(fn ($query) => $query->where('type', UserTypeEnum::Customer->value)),
                Tab::make('Supplier')
                    ->badge(fn ($count) => $count)
                    ->query(fn ($query) => $query->where('type', UserTypeEnum::Supplier->value)),
        ];
    }
}
