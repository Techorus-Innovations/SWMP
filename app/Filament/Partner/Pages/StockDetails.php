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
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use App\Models\PlantArea;
class StockDetails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.partner.pages.stock-details';

    /** Populated from URL query parameter */
    #[Url]
    public string $materialCode = '';

    // /** Populated from URL query parameter */
    // #[Url]
    // public string $warehouseCode = '';

    protected $sapService;

    private ?array $response = null;

    private ?Material $material = null;

    public static function canAccess(): bool
    {
        return filament()->auth()->user()->canViewStock();
    }

    public function boot(): void
    {
        $this->sapService = new SapService;
        $this->response = $this->sapService->getStockByMaterialCode(
            filament()->auth()->user()->businessPartner->bp_code,
            $this->materialCode
        );
        // Fetch material details from database
        $this->material = Material::where('code', $this->materialCode)->first();


        $locationCodes = collect($this->response['batches'] ?? [])
            ->flatMap(fn($batch) => collect($batch['locations'] ?? [])->pluck('location_code'))
            ->unique()
            ->values();

        $plantAreas = PlantArea::whereIn('code', $locationCodes)
            ->get()
            ->keyBy('code');

        $this->response['batches'] = collect($this->response['batches'] ?? [])
            ->map(function ($batch) use ($plantAreas) {
                $batch['locations'] = collect($batch['locations'] ?? [])
                    ->map(function ($location) use ($plantAreas) {
                        $plantArea = $plantAreas->get($location['location_code'] ?? null);

                        $location['location_name_en'] = $plantArea?->name_en ?? '—';
                        $location['location_name_ar'] = $plantArea?->name_ar ?? '—';

                        return $location;
                    })
                    ->all();

                return $batch;
            })
            ->all();
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

        return $schema->components([
            Section::make(__('Stock Summary'))
                ->columns(2)
                ->schema([
                    TextEntry::make('material_code')
                        ->label(__('Material Code'))
                        ->state($record['material_code'] ?? '—'),
                    TextEntry::make('material_quantity')
                        ->label(__('Total Quantity'))
                        ->badge()
                        ->state($record['material_quantity'] ?? '-')
                        ->suffix(' ' . ($record['material_quantity_unit'] ?? '')),
                    TextEntry::make('name_en')
                        ->label(__('Material Name (EN)'))
                        ->state($this->material?->name_en ?? '-'),
                    TextEntry::make('name_ar')
                        ->label(__('Material Name (AR)'))
                        ->state($this->material?->name_ar ?? '-'),
                    TextEntry::make('gtin')
                        ->label(__('GTIN Code'))
                        ->state($this->material?->gtin_code ?? '-'),
                ]),

            Section::make(__('Batches'))
                ->schema([
                    RepeatableEntry::make('batches')
                        ->hiddenLabel()
                        ->state($record['batches'] ?? [])
                        ->schema([
                            Grid::make(4)
                                ->schema([
                                    TextEntry::make('batch_code')
                                        ->label(__('Batch Code'))
                                        ->weight('bold'),
                                    TextEntry::make('batch_quantity')
                                        ->label(__('Batch Quantity'))
                                        ->badge()
                                        ->suffix(fn($record) => ' ' . ($record['batch_quantity_unit'] ?? '')),
                                    TextEntry::make('expiration_date')
                                        ->label(__('Expiration Date')),
                                    TextEntry::make('manufacture_date')
                                        ->label(__('Manufacture Date')),
                                ]),

                            Section::make(__('Locations'))
                                ->collapsible()
                                ->collapsed()
                                ->compact()
                                ->schema([
                                    RepeatableEntry::make('locations')
                                        ->hiddenLabel()
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    TextEntry::make('location_code')
                                                        ->label(__('Location Code')),
                                                    TextEntry::make('location_quantity')
                                                        ->label(__('Location Quantity'))
                                                        ->badge()
                                                        ->color('gray')
                                                        ->suffix(fn($record) => ' ' . ($record['location_quantity_unit'] ?? '')),
                                                    TextEntry::make('location_name_en')
                                                        ->label(__('Location Name (EN)')),
                                                    TextEntry::make('location_name_ar')
                                                        ->label(__('Location Name (AR)')),
                                                ]),
                                        ])
                                        ->contained(false),
                                ]),
                        ])
                        ->contained(true),
                ]),

        ]);
    }
    // public function infolist(Schema $schema): Schema
    // {
    //     $record = $this->response;
    //     // $locale = app()->getLocale();
    //     // $materialName = $locale === 'ar' ? $this->material?->name_ar : $this->material?->name_en;

    //     return $schema->components([
    //         Section::make(__('Stock Summary'))
    //             ->columns(2)
    //             ->schema([
    //                 TextEntry::make('material_code')
    //                     ->label(__('Material Code'))
    //                     ->state($record['material_code'] ?? '—'),
    //                 TextEntry::make('material_quantity')
    //                     ->label(__('Total Quantity'))
    //                     ->badge()
    //                     ->state($record['material_quantity'] ?? '-')
    //                     ->suffix(' '.$record['material_quantity_unit']),
    //                 TextEntry::make('name_en')
    //                     ->label(__('Material Name (EN)') ?? '-')
    //                     ->state($this->material?->name_en),
    //                 TextEntry::make('name_ar')
    //                     ->label(__('Material Name (AR)') ?? '-')
    //                     ->state($this->material?->name_ar),
    //                 TextEntry::make('gtin')
    //                     ->label(__('GTIN Code') ?? '-')
    //                     ->state($this->material?->gtin_code),
    //                 // TextEntry::make('warehouse_code')
    //                 //     ->label(__('Warehouse'))
    //                 //     ->state($record['warehouse_code'] ?? '—'),
    //             ]),
    //     ]);
    // }

    // public function table(Table $table): Table
    // {
    //     return $table
    //         ->records(function () {
    //             $batches = collect($this->response['batches'] ?? [])->toArray();
    //             $currentPage = $this->getTablePage();
    //             $perPage = $this->getTableRecordsPerPage() ?? 10;

    //             $paginated = array_slice($batches, ($currentPage - 1) * $perPage, $perPage);

    //             return new LengthAwarePaginator(
    //                 $paginated,
    //                 count($batches),
    //                 $perPage,
    //                 $currentPage
    //             );
    //         })
    //         ->columns([

    //             TextColumn::make('batch_code')
    //                 ->label(__('Batch Code')) ,
    //             TextColumn::make('batch_quantity')
    //                 ->label(__('Batch Quantity'))
    //                 ->suffix(fn ($record) => ' ' . ($record['batch_quantity_unit'] ?? '')),
    //             TextColumn::make('expiration_date')
    //                 ->label(__('Expiration Date')),
    //             TextColumn::make('manufacture_date')
    //                 ->label(__('Manufacture Date')),
    //         ])
    //         ->paginated([10, 25, 50])
    //         ->filters([])
    //         ->actions([])
    //         ->bulkActions([]);
    // }
}
