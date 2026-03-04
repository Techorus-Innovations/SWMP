<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Harris21\Fuse\CircuitBreaker;

class ListDraftInbounds extends ListRecords
{
    protected static string $resource = DraftInboundResource::class;
    private $circuitBreaker;

    public function __construct() {
        $this->circuitBreaker = new CircuitBreaker('sap');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->tooltip(fn () => $this->circuitBreaker->isOpen() ? 'External service is currently unavailable' : null)
            ->disabled(fn (): bool => $this->circuitBreaker->isOpen()),
        ];
    }
}
