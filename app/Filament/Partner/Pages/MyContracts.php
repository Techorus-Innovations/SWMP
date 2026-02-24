<?php

namespace App\Filament\Partner\Pages;

use App\Services\SapService;
use Filament\Actions\ViewAction;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class MyContracts extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.partner.pages.my-contracts';

    public function table(Table $table): Table
    {
        $sapService = new SapService;

        return $table
            ->records(function () use ($sapService) {
                $allProducts = collect($sapService->getContracts(filament()->auth()->user()->businessPartner->bp_code));

                $currentPage = $this->getTablePage();
                $perPage = $this->getTableRecordsPerPage() ?? 10;

                $currentItems = $allProducts
                    ->slice(($currentPage - 1) * $perPage, $perPage)
                    ->values();

                return new LengthAwarePaginator(
                    $currentItems,
                    $allProducts->count(),
                    $perPage,
                    $currentPage
                );
            })
            ->columns([
                    TextColumn::make('contract_code'),
                    TextColumn::make('contract_start'),
                    TextColumn::make('contract_end'),
                ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([
                    // ViewAction::make()
                ])
            ->bulkActions([]);
    }
}
