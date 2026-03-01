<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Schemas;

use App\Models\Material;
use App\Services\SapService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DraftInboundForm
{
    public static function getContractObject(Schema $schema, $sapService) {
        if($schema->getOperation() === 'edit') {
            return TextInput::make('contract_id')
                ->label('Contract ID')
                ->hiddenOn('create')
                ->disabled();
        } else {
            return Select::make('contract_id')
                ->label('Contract')
                ->options(
                    collect($sapService->getContracts(filament()->auth()->user()->businessPartner->bp_code, 100, 1, 'active')['paginatedContracts'])->mapWithKeys(fn ($m) => [
                                    $m['contract_code'] => $m['contract_code'],
                                ])
                )
                ->hiddenOn('edit')
                ->searchable()
                ->required();
        }
    }

    public static function configure(Schema $schema): Schema
    {
        $sapService = new SapService();
        $areas = '';
        if($schema->getOperation() === 'edit' && $schema->getLivewire()->record->contract_id != null) {
            $selectedContract = $sapService->getContractById(filament()->auth()->user()->businessPartner->bp_code, $schema->getLivewire()->record->contract_id);
            $areas = collect($selectedContract['areas'])->pluck('area_code')->unique();
        }
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            self::getContractObject($schema, $sapService),    


            Repeater::make('materials')
                ->label('Materials')
                ->schema([
                    Select::make('material_id')
                        ->label('Material')
                        ->options(
                            function() use($areas): array {
                                return Material::whereHas('areas', function ($query) use ($areas) {
                                    $query->whereIn('code', $areas);
                                })->get()
                                ->mapWithKeys(fn ($m) => [
                                    $m->id => (app()->getLocale() === 'ar' ? $m->name_ar : $m->name_en),
                                ])->toArray();
                            }
                        )
                        ->searchable()
                        ->required()
                        ->distinct(),
                    TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                ])
                ->defaultItems(0)
                ->addActionLabel('Add Material')
                ->columnSpanFull()
                ->hiddenOn('create'),
        ]);
    }
}
