<?php

namespace App\Filament\Partner\Resources\DraftOutbound\RelationManagers;

use App\Models\DraftOutboundMaterial;
use App\Services\SapService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class DraftOutboundMaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    protected static ?string $title = 'Materials';

    public $stock = [];

    public $options = [];

    public function mount(): void
    {
        $sapService = new SapService;
        $this->stock = $sapService->getAllStock(filament()->auth()->user()->businessPartner->bp_code);
        // dd($this->stock);
        $this->options = collect($this->stock)->pluck('material_name', 'material_code');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('material_code')
                    ->label(__('Material'))
                    ->options(function () {
                        $appliedCodes = $this->getRelationship()->pluck('material_code')
                            ->toArray();

                        return collect($this->options)
                            ->forget($appliedCodes)
                            ->toArray();
                    })
                    ->required()
                    ->searchable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('material_id')
            ->columns([
                TextColumn::make('material.name_en')
                    ->label(__('Material Name (EN)'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('material.code')
                    ->label(__('Material Code'))
                    ->searchable(),

                TextInputColumn::make('quantity')
                    ->rules(function (DraftOutboundMaterial $record): array {
                        return [
                            'required',
                            'numeric',
                            'min:0',
                            "max:{$record->max_quantity}",
                        ];
                    })
                    ->label(__('Quantity'))
                    ->afterStateUpdated(function () {
                        $this->dispatch('materials-updated');
                    })
                    ->suffix(function (DraftOutboundMaterial $record) {
                        return $record->quantity_unit;
                    })
                    ->sortable(),

                TextColumn::make('max_quantity')
                    ->label(__('Max Quantity'))
                    ->sortable(),

                TextColumn::make('inbound_request_id')
                    ->label(__('Inbound Request ID'))
                    ->sortable(),

                TextColumn::make('manufacture_date')
                    ->label(__('Manufacture Date'))
                    ->sortable(),

                TextColumn::make('expiration_date')
                    ->label(__('Expiration Date'))
                    ->sortable(),

                TextColumn::make('batch')
                    ->label(__('Batch'))
                    ->sortable(),
            ])
            ->filters([
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Add Items'))
                    ->action(function (array $data): void {
                        $selectedStock = collect($this->stock)
                            ->firstWhere('material_code', $data['material_code']);
                        foreach ($selectedStock['inbounds'] as $inbound) {
                            $this->getRelationship()->create([
                                'material_code' => $selectedStock['material_code'],
                                'max_quantity' => $inbound['quantity'],
                                'inbound_request_id' => $inbound['inbound_id'],
                                'quantity_unit' => $inbound['measuring_unit'],
                                'inbound_request_item_code' => $inbound['item_number'],
                                'location' => $inbound['location'],
                                'manufacture_date' => $inbound['manufacture_date'],
                                'expiration_date' => $inbound['expiration_date'],
                                'batch' => $inbound['batch'],
                            ]);
                        }

                        $this->dispatch('materials-updated');
                    }),
            ])
            ->actions([

                Action::make('deleteGroup')
                    ->label(__('Delete Material Group'))
                    ->label('Delete Material Group')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete all batches?')
                    ->modalDescription('This will remove all inbound records for this specific material.')
                    ->action(function (DraftOutboundMaterial $record): void {
                        $this->getRelationship()
                            ->where('material_code', $record->material_code)
                            ->delete();

                        $this->dispatch('materials-updated');
                    }),
            ])
            ->bulkActions([])
            ->defaultGroup('material.code');
    }
}
