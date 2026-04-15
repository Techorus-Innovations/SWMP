<?php

namespace App\Filament\Partner\Resources\DraftOutbound\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DraftOutboundForm
{
    public static function configure(Schema $schema): Schema
    {
        $isEdit = $schema->getOperation() === 'edit';
        $bpCode = filament()->auth()->user()->businessPartner->bp_code;

        return $schema
            ->components([
                Select::make('shipping_type')
                    ->label('Shipping Type')
                    ->options([
                        'receive_from_warehouse' => 'Receive from Warehouse',
                    ])
                    ->dehydrated(false)
                    ->live()
                    ->disabled($isEdit)
                    ->afterStateUpdated(function (Set $set, ?string $state) use ($bpCode) {
                        if ($state === 'receive_from_warehouse') {
                            $set('ship_to_party', $bpCode);
                        }
                    })
                    ->afterStateHydrated(function (Set $set, $record) use ($bpCode) {
                        if ($record && $record->ship_to_party === $bpCode) {
                            $set('shipping_type', 'receive_from_warehouse');
                        }
                    }),
                TextInput::make('ship_to_party')
                    ->label('Ship To Party')
                    ->required()
                    ->maxLength(255)
                    ->dehydrated(true)
                    ->disabled(fn (Get $get): bool => $isEdit || $get('shipping_type') === 'receive_from_warehouse'),
            ]);
    }
}
