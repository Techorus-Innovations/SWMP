<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Schemas;

use App\Services\SapService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DraftInboundForm
{
    public static function getContractObject(Schema $schema, $sapService)
    {
        if ($schema->getOperation() === 'edit' || $schema->getOperation() === 'view') {
            return TextInput::make('contract_id')
                ->label('Contract ID')
                ->hiddenOn('create')
                ->disabled();
        } else {
            return Select::make('contract_id')
                ->label('Contract')
                ->options(
                    collect($sapService->getContracts(filament()->auth()->user()->businessPartner->bp_code, 100, 1, 'active')['paginatedContracts'])->mapWithKeys(fn ($m) => [
                        $m['contract_code'] => $m['contract_code'].' - '.$m['contract_end'].' - ('.collect($m['areas'] ?? [])->pluck('area_description')->implode(' - ').')',
                    ])
                )
                ->hiddenOn('edit')
                ->searchable()
                ->required();
        }
    }

    public static function configure(Schema $schema): Schema
    {
        $sapService = new SapService;

        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            self::getContractObject($schema, $sapService),
        ]);
    }
}

