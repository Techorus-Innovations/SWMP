<?php

namespace App\Filament\Partner\Pages;

use App\Services\SapService;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class MyContracts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.partner.pages.my-contracts';

    public string $activeTab = 'active';

    protected $sapService;

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
                $reponse = $this->sapService->getContracts(filament()->auth()->user()->businessPartner->bp_code, $perPage, $currentPage, $this->activeTab);
                return new LengthAwarePaginator(
                    $reponse['paginatedContracts'],
                    $reponse['totalCount'],
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
                TextColumn::make('areas')
                    ->label('Areas')
                    ->state(fn ($record) => collect($record['areas'] ?? [])->pluck('area_description')->all())
                    ->badge()
                    ->color('primary'),
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
