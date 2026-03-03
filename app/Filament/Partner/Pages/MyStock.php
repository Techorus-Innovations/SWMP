<?php

namespace App\Filament\Partner\Pages;

use App\Models\Material;
use App\Services\SapService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class MyStock extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static string|\UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'My Stock';

    protected string $view = 'filament.partner.pages.my-stock';

    protected $sapService;

    public static function canAccess(): bool
    {
        return filament()->auth()->user()->canViewStock();
    }

    public function boot(): void
    {
        $this->sapService = new SapService;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                $currentPage = $this->getTablePage();
                $perPage = $this->getTableRecordsPerPage() ?? 10;
                $response = $this->sapService->getStock(
                    filament()->auth()->user()->businessPartner->bp_code,
                    $perPage,
                    $currentPage
                );
                
                // Enrich stock data with material details and total quantity
                $locale = app()->getLocale();
                $enrichedStock = collect($response['paginatedStock'] ?? [])->map(function ($stock) use ($locale) {
                    $material = Material::where('code', $stock['material_code'])->first();
                    $stock['material_name'] = ($locale === 'ar' ? $material?->name_ar : $material?->name_en);
                    $stock['total_quantity'] = collect($stock['inbounds'] ?? [])->sum('quantity');
                    return $stock;
                })->toArray();

                return new LengthAwarePaginator(
                    $enrichedStock,
                    $response['totalCount'],
                    $perPage,
                    $currentPage
                );
            })
            ->columns([
                TextColumn::make('material_code')
                    ->label(__('Material Code')),
                TextColumn::make('material_name')
                    ->label(__('Material Name')),
                TextColumn::make('warehouse_code')
                    ->label(__('Warehouse')),
                TextColumn::make('total_quantity')
                    ->label(__('Total Quantity'))
                    ->badge()
            ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([
                Action::make('view')
                    ->label(__('View Details'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => StockDetails::getUrl([
                        'materialCode' => $record['material_code'],
                        'warehouseCode' => $record['warehouse_code'],
                    ])),
            ])
            ->bulkActions([]);
    }
}
