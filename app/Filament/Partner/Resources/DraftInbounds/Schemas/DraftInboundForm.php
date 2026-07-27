<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Schemas;

use App\Models\Material;
use App\Models\PlantArea;
use App\Services\SapService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class DraftInboundForm
{
    public static function getContractObject(Schema $schema, $sapService)
    {
        if ($schema->getOperation() === 'edit' || $schema->getOperation() === 'view') {
            return TextInput::make('contract_id')
                ->label('Contract ID')
                ->hiddenOn('create')
                ->disabled();
        } else {
            // dd($sapService->getContracts(filament()->auth()->user()->businessPartner->bp_code, 100, 1, 'active')['paginatedContracts']);

            return Select::make('contract_id')
                ->label('Contract')
                ->options(
                    collect($sapService->getContracts(filament()->auth()->user()->businessPartner->bp_code, 100, 1, 'active')['paginatedContracts'])->mapWithKeys(fn ($m) => [
                        $m['contract_code'] => $m['contract_code'].' - '.$m['contract_end'].' - ('.collect($m['areas'] ?? [])->pluck('area_description')->implode(' - ').')',
                    ])
                )
                ->hiddenOn('edit')
                ->searchable()
                ->required();
        }
    }

    public static function getMaterialObject(Schema $schema, $sapService)
    {
        if ($schema->getOperation() === 'edit' || $schema->getOperation() === 'view') {
            if ($schema->getOperation() === 'view' || ($schema->getOperation() === 'edit' && $schema->getLivewire()->record->contract_id != null)) {
                $selectedContract = $sapService->getContractById(filament()->auth()->user()->businessPartner->bp_code, $schema->getLivewire()->record->contract_id);
                $areas = collect($selectedContract['areas'])->pluck('area_code')->unique();
            }

            return Repeater::make('materials')
                ->label('Materials')
                ->columns(3)
                ->schema([
                    Select::make('material_id')
                        ->label('Material')
                        ->options(
                            function () use ($areas): array {
                                $latest_sync_time = Material::max(('sync_at'));


                                return Material::whereHas('areas', function ($query) use ($areas, $latest_sync_time) {
                                    $query->whereIn('code', $areas)->where('sync_at', $latest_sync_time);
                                })->get()
                                    ->mapWithKeys(fn ($m) => [
                                        $m->id => (app()->getLocale() === 'ar' ? $m->name_ar : $m->name_en),
                                    ])->toArray();
                            }
                        )
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) use ($areas) {
                            if (! $state) {
                                $set('plant_area_id', null);

                                return;
                            }
                            $firstArea = PlantArea::whereIn('code', $areas)
                                ->whereHas('materials', function ($query) use ($state) {
                                    $query->where('materials.id', $state);
                                })
                                ->first()?->id;

                            $set('plant_area_id', $firstArea);
                        })
                        ->required()
                        // ->distinct()
                        ->rules([
                            function (Get $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $batch = $get('batch');
                                    if (! $value || ! $batch) {
                                        return;
                                    }

                                    $allMaterials = $get('../../materials') ?? [];
                                    $duplicates = 0;
                                    foreach ($allMaterials as $item) {
                                        if (
                                            isset($item['material_id'], $item['batch']) &&
                                            (string) $item['material_id'] === (string) $value &&
                                            trim((string) $item['batch']) === trim((string) $batch)
                                        ) {
                                            $duplicates++;
                                        }
                                    }

                                    if ($duplicates > 1) {
                                        $fail(__('The combination of material and batch must be unique within the draft inbound.'));
                                    }
                                };
                            },
                        ]),
                    Select::make('plant_area_id')
                        ->label(__('Plant Area'))
                        ->options(function (Get $get) use ($areas): array {
                            $materialId = $get('material_id');
                            if (! $materialId) {
                                return [];
                            }

                            return PlantArea::whereIn('code', $areas)
                                ->whereHas('materials', function ($query) use ($materialId) {
                                    $query->where('materials.id', $materialId);
                                })
                                ->get()
                                ->mapWithKeys(fn ($pa) => [
                                    $pa->id => (app()->getLocale() === 'ar' ? $pa->name_ar : $pa->name_en),
                                ])
                                ->toArray();
                        })
                        ->searchable()
                        ->required(),
                    TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->suffix('EA'),
                    TextInput::make('batch')
                        ->label(__('Batch'))
                        ->required()
                        ->string()
                        ->maxLength(10)
                        ->rules([
                            function (Get $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $materialId = $get('material_id');
                                    if (! $materialId || ! $value) {
                                        return;
                                    }

                                    $allMaterials = $get('../../materials') ?? [];
                                    $duplicates = 0;
                                    foreach ($allMaterials as $item) {
                                        if (
                                            isset($item['material_id'], $item['batch']) &&
                                            (string) $item['material_id'] === (string) $materialId &&
                                            trim((string) $item['batch']) === trim((string) $value)
                                        ) {
                                            $duplicates++;
                                        }
                                    }

                                    if ($duplicates > 1) {
                                        $fail(__('The combination of material and batch must be unique within the draft inbound.'));
                                    }
                                };
                            },
                        ]),
                    DatePicker::make('manufacture_date')
                        ->label(__('Manufacture Date'))
                        ->required()
                        ->displayFormat('Y-m-d')
                        ->maxDate(Carbon::now()->addMonth())
                        ->rules([
                            function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    if ($value && Carbon::parse($value)->gt(Carbon::now()->addMonth())) {
                                        $fail(__('Manufacture date cannot be more than 1 month in the future.'));
                                    }
                                };
                            },
                        ])
                        ->live(),
                    DatePicker::make('expiration_date')
                        ->label(__('Expiration Date'))
                        ->required()
                        ->displayFormat('Y-m-d')
                        ->after('manufacture_date')
                        ->rules([
                            fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                $mfgDate = $get('manufacture_date');
                                if ($mfgDate && $value && strtotime($value) <= strtotime($mfgDate)) {
                                    $fail(__('Expiration date must be after manufacture date.'));
                                }
                            },
                        ]),
                ])
                ->reorderable(false)
                ->defaultItems(0)
                ->addActionLabel('Add Material')
                ->columnSpanFull()
                ->hiddenOn('create');
        }
    }

    public static function configure(Schema $schema): Schema
    {
        $sapService = new SapService;

        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            self::getContractObject($schema, $sapService),
            self::getMaterialObject($schema, $sapService),
        ]);
    }
}
