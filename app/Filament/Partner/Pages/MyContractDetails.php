<?php

namespace App\Filament\Partner\Pages;

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

class MyContractDetails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.partner.pages.my-contract-details';

    #[Url]
    public string $contractId = '';

    protected $sapService;

    private ?array $response = null;

    public function boot(): void
    {
        $this->sapService = new SapService;
        $this->response = $this->sapService->getContractById(
            filament()->auth()->user()->businessPartner->bp_code,
            $this->contractId
        );
    }

    public function getTitle(): string
    {
        return __('Contract').' #'.$this->contractId;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->response;

        return $schema->components([
            Section::make(__('Contract Details'))
                ->columns(3)
                ->schema([
                    TextEntry::make('contract_code')
                        ->label(__('Contract Code'))
                        ->state($record['contract_code'] ?? '—'),
                    TextEntry::make('contract_start')
                        ->label(__('Start Date'))
                        ->state($record['contract_start'] ?? '—'),
                    TextEntry::make('contract_end')
                        ->label(__('End Date'))
                        ->state($record['contract_end'] ?? '—'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                $items = collect($this->response['areas'] ?? [])->toArray();
                $currentPage = $this->getTablePage();
                $perPage = $this->getTableRecordsPerPage() ?? 10;

                $paginated = array_slice($items, ($currentPage - 1) * $perPage, $perPage);

                return new LengthAwarePaginator(
                    $paginated,
                    count($items),
                    $perPage,
                    $currentPage
                );
            })
            ->columns([
                TextColumn::make('area_code')
                    ->label(__('Area Code')),
                TextColumn::make('area_description')
                    ->label(__('Area Description')),
                TextColumn::make('quantity')
                    ->label(__('Quantity')),
                TextColumn::make('measuring_unit')
                    ->label(__('Measuring Unit')),
            ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }
}
