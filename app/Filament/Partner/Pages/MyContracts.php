<?php

namespace App\Filament\Partner\Pages;

use App\Services\SapService;
use Carbon\Carbon;
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

    public string $activeTab = 'active';

    public function table(Table $table): Table
    {
        $sapService = new SapService;
        $activeTab = $this->activeTab;

        return $table
            ->records(function () use ($sapService, $activeTab) {
                $today = Carbon::today();

                $allContracts = collect($sapService->getContracts(filament()->auth()->user()->businessPartner->bp_code));

                $filtered = $allContracts->filter(function ($contract) use ($today, $activeTab) {
                    $endDate = $contract['contract_end'] ? Carbon::parse($contract['contract_end']) : null;

                    if ($activeTab === 'active') {
                        return $endDate && $endDate->greaterThanOrEqualTo($today);
                    }

                    // inactive: end date in the past or null
                    return !$endDate || $endDate->lessThan($today);
                });

                $currentPage = $this->getTablePage();
                $perPage = $this->getTableRecordsPerPage() ?? 10;

                $currentItems = $filtered
                    ->slice(($currentPage - 1) * $perPage, $perPage)
                    ->values();

                return new LengthAwarePaginator(
                    $currentItems,
                    $filtered->count(),
                    $perPage,
                    $currentPage
                );
            })
            ->columns([
                TextColumn::make('contract_code')
                    ->label('Contract Code'),
                TextColumn::make('contract_start')
                    ->label('Start Date'),
                TextColumn::make('contract_end')
                    ->label('End Date'),
            ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([
                // ViewAction::make()
            ])
            ->bulkActions([]);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function getTabs(): array
    {
        return [
            ['value' => 'active',   'label' => __('Active')],
            ['value' => 'inactive', 'label' => __('Inactive')],
        ];
    }
}
