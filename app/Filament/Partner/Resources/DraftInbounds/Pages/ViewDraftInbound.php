<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use App\Services\SapService;
use DateTime;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class ViewDraftInbound extends ViewRecord
{
    protected static string $resource = DraftInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitInbound')
                ->label('Submit Inbound')
                ->color('success')
                ->schema([
                    DateRangePicker::make('date')
                            ->displayFormat('YYYY-MM-DD')
                            ->disableRanges()
                            // ->firstDayOfWeek(0)
                            ->minDate(Carbon::now()->addDays(2))
                            ->maxDate(Carbon::now()->addMonth())
                            ->autoApply()
                            ->alwaysShowCalendar()
                            ->live()
                            ->singleCalendar()
                            ->required(),
                    Select::make('time_slot')
                            ->label('Select Time Slot')
                            ->disabled(fn (Get $get) : bool => ! filled($get(path:'date')))
                            ->options([
                                '08' => '08:00 AM',
                                '09' => '09:00 AM',
                                '10' => '10:00 AM',
                                '11' => '11:00 AM',
                                '12' => '12:00 PM',
                                '13' => '01:00 PM',
                                '14' => '02:00 PM',
                                '15' => '03:00 PM',
                                '16' => '04:00 PM',
                                '17' => '05:00 PM',
                                '18' => '06:00 PM',
                                '19' => '07:00 PM',
                                '20' => '08:00 PM',
                            ])
                            ->required(),
                    ])
                ->action(function (array $data) {
                    $date = $data['date'];
                    $time = $data['time_slot'];
                    $bpcode = filament()->auth()->user()->businessPartner->bp_code;
                    $contractId = $this->record->contract_id;
                    $materialCodesWithQuantity = $this->record->getMaterialCodesWithQuantity();
                    // dd($materialCodesWithQuantity);
                    $sapService = new SapService();
                    $sapService->createInbound($bpcode, $contractId, $materialCodesWithQuantity, $date, $time);
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['materials'] = $this->record->materials
            ->map(fn ($material) => [
                'material_id' => $material->id,
                'quantity' => $material->pivot->quantity,
            ])
            ->toArray();

        return $data;
    }
}
