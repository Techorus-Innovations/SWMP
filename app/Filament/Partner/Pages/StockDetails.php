<?php

namespace App\Filament\Partner\Pages;

use App\Models\Material;
use App\Services\SapService;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;

class StockDetails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.partner.pages.stock-details';

    /** Populated from URL query parameter */
    #[Url]
    public string $materialCode = '';
    /** Populated from URL query parameter */
    #[Url]
    public string $warehouseCode = '';

    protected $sapService;

    private ?array $response = null;
    private ?Material $material = null;
    private ?int $totalInboundQuantity = null;

    public function boot(): void
    {
        $this->sapService = new SapService;
        $this->response = $this->sapService->getStockByMaterialCodeAndWarehouseCode(
            filament()->auth()->user()->businessPartner->bp_code,
            $this->materialCode,
            $this->warehouseCode
        );
         // Fetch material details from database
        $this->material = Material::where('code', $this->materialCode)->first();

        // Calculate total inbound quantity
        if ($this->response) {
            $this->totalInboundQuantity = collect($this->response['inbounds'] ?? [])->sum('quantity');
        }
    }

    public function getTitle(): string
    {
        $locale = app()->getLocale();
        $materialName = $locale === 'ar' ? $this->material?->name_ar : $this->material?->name_en;
        
        return __('Stock Details') . ' - ' . $materialName;
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->response;
        $locale = app()->getLocale();
        $materialName = $locale === 'ar' ? $this->material?->name_ar : $this->material?->name_en;

        return $schema->components([
            Section::make(__('Stock Summary'))
                ->columns(2)
                ->schema([
                    TextEntry::make('material_code')
                        ->label(__('Material Code'))
                        ->state($record['material_code'] ?? '—'),
                    TextEntry::make('material_name')
                        ->label(__('Material Name') ?? '-')
                        ->state($materialName),
                    TextEntry::make('warehouse_code')
                        ->label(__('Warehouse'))
                        ->state($record['warehouse_code'] ?? '—'),
                    TextEntry::make('total_inbound_quantity')
                        ->label(__('Total Inbound Quantity'))
                        ->badge()
                        ->state($this->totalInboundQuantity ?? '-'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                $inbounds = collect($this->response['inbounds'] ?? [])->toArray();
                $currentPage = $this->getTablePage();
                $perPage = $this->getTableRecordsPerPage() ?? 10;

                $paginated = array_slice($inbounds, ($currentPage - 1) * $perPage, $perPage);

                return new LengthAwarePaginator(
                    $paginated,
                    count($inbounds),
                    $perPage,
                    $currentPage
                );
            })
            ->columns([
                TextColumn::make('inbound_id')
                    ->label(__('Inbound ID')),
                TextColumn::make('quantity')
                    ->label(__('Quantity')),
                TextColumn::make('measuring_unit')
                    ->label(__('Unit')),
            ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }
}
