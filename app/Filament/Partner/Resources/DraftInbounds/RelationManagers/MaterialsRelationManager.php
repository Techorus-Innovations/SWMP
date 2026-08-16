<?php

namespace App\Filament\Partner\Resources\DraftInbounds\RelationManagers;

use App\Models\DraftInboundMaterial;
use App\Models\Material;
use App\Models\PlantArea;
use App\Services\DraftInboundMaterialImporter;
use App\Services\SapService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'inboundMaterials';

    protected static ?string $title = 'Materials';

    public ?array $contractAreas = null;

    public function mount(): void
    {
        $this->loadContractAreas();
    }

    public function getContractAreas(): Collection
    {
        if ($this->contractAreas === null) {
            $this->loadContractAreas();
        }

        return collect($this->contractAreas ?? []);
    }

    protected function loadContractAreas(): void
    {
        $owner = $this->getOwnerRecord();
        if (!$owner || !$owner->contract_id) {
            $this->contractAreas = [];

            return;
        }

        $bpCode = filament()->auth()->user()?->businessPartner?->bp_code;
        if (!$bpCode) {
            $this->contractAreas = [];

            return;
        }

        $sapService = new SapService;
        $selectedContract = $sapService->getContractById($bpCode, $owner->contract_id);

        $this->contractAreas = collect($selectedContract['areas'] ?? [])->pluck('area_code')->unique()->values()->toArray();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('material_id')
                    ->label(__('Material'))
                    ->options(function (): array {
                        $areas = $this->getContractAreas();
                        $latest_sync_time = Material::max('sync_at');

                        return Material::whereHas('areas', function ($query) use ($areas, $latest_sync_time) {
                            $query->whereIn('code', $areas)->where('sync_at', $latest_sync_time);
                        })->get()
                            ->mapWithKeys(fn($m) => [
                                $m->id => (app()->getLocale() === 'ar' ? $m->name_ar : $m->name_en) . " " . $m->gtin_code,
                            ])->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if (!$state) {
                            $set('plant_area_id', null);

                            return;
                        }
                        $areas = $this->getContractAreas();
                        $firstArea = PlantArea::whereIn('code', $areas)
                            ->whereHas('materials', function ($query) use ($state) {
                                $query->where('materials.id', $state);
                            })
                            ->first()?->id;

                        $set('plant_area_id', $firstArea);
                    })
                    ->required()
                    ->rules([
                        function (Get $get, ?DraftInboundMaterial $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                $batch = $get('batch');
                                $plantAreaId = $get('plant_area_id');
                                if (!$value || !$batch || !$plantAreaId) {
                                    return;
                                }

                                $ownerRecord = $this->getOwnerRecord();
                                if (!$ownerRecord) {
                                    return;
                                }

                                $query = DraftInboundMaterial::where('draft_inbound_id', $ownerRecord->id)
                                    ->where('material_id', $value)
                                    ->where('batch', trim((string) $batch))
                                    ->where('plant_area_id', $plantAreaId);

                                if ($record && $record->exists) {
                                    $query->where('id', '!=', $record->id);
                                }

                                if ($query->exists()) {
                                    $fail(__('The combination of material, batch, and storage location must be unique within the draft inbound.'));
                                }
                            };
                        },
                    ]),

                Select::make('plant_area_id')
                    ->label(__('Storage Location'))
                    ->options(function (Get $get): array {
                        $materialId = $get('material_id');
                        if (!$materialId) {
                            return [];
                        }
                        $areas = $this->getContractAreas();

                        return PlantArea::whereIn('code', $areas)
                            ->whereHas('materials', function ($query) use ($materialId) {
                                $query->where('materials.id', $materialId);
                            })
                            ->get()
                            ->mapWithKeys(fn($pa) => [
                                $pa->id => (app()->getLocale() === 'ar' ? $pa->name_ar : $pa->name_en),
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->required()
                    ->rules([
                        function (Get $get, ?DraftInboundMaterial $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                $materialId = $get('material_id');
                                $batch = $get('batch');
                                if (!$materialId || !$batch || !$value) {
                                    return;
                                }

                                $ownerRecord = $this->getOwnerRecord();
                                if (!$ownerRecord) {
                                    return;
                                }

                                $query = DraftInboundMaterial::where('draft_inbound_id', $ownerRecord->id)
                                    ->where('material_id', $materialId)
                                    ->where('batch', trim((string) $batch))
                                    ->where('plant_area_id', $value);

                                if ($record && $record->exists) {
                                    $query->where('id', '!=', $record->id);
                                }

                                if ($query->exists()) {
                                    $fail(__('The combination of material, batch, and storage location must be unique within the draft inbound.'));
                                }
                            };
                        },
                    ]),

                TextInput::make('quantity')
                    ->label(__('Quantity'))
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
                        function (Get $get, ?DraftInboundMaterial $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                $materialId = $get('material_id');
                                $plantAreaId = $get('plant_area_id');
                                if (!$materialId || !$value || !$plantAreaId) {
                                    return;
                                }

                                $ownerRecord = $this->getOwnerRecord();
                                if (!$ownerRecord) {
                                    return;
                                }

                                $query = DraftInboundMaterial::where('draft_inbound_id', $ownerRecord->id)
                                    ->where('material_id', $materialId)
                                    ->where('batch', trim((string) $value))
                                    ->where('plant_area_id', $plantAreaId);

                                if ($record && $record->exists) {
                                    $query->where('id', '!=', $record->id);
                                }

                                if ($query->exists()) {
                                    $fail(__('The combination of material, batch, and storage location must be unique within the draft inbound.'));
                                }
                            };
                        },
                    ]),

                DatePicker::make('manufacture_date')
                    ->label(__('Manufacture Date'))
                    ->required()
                    ->displayFormat('d/m/Y')
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
                    ->displayFormat('d/m/Y')
                    ->after('manufacture_date')
                    ->rules([
                        fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $mfgDate = $get('manufacture_date');
                            if ($mfgDate && $value && strtotime($value) <= strtotime($mfgDate)) {
                                $fail(__('Expiration date must be after manufacture date.'));
                            }
                        },
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table
            ->recordTitleAttribute('batch')
            ->columns([
                TextColumn::make('material.code')
                    ->label(__('Code'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('material_name')
                    ->label(__('Material'))
                    ->state(fn(DraftInboundMaterial $record) => $locale === 'ar' ? $record->material?->name_ar : $record->material?->name_en)
                    ->searchable(query: function ($query, string $search) use ($locale) {
                        $query->whereHas('material', function ($q) use ($search, $locale) {
                            $column = $locale === 'ar' ? 'name_ar' : 'name_en';
                            $q->where($column, 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('plantArea')
                    ->label(__('Storage Location'))
                    ->state(fn(DraftInboundMaterial $record) => $locale === 'ar' ? $record->plantArea?->name_ar : $record->plantArea?->name_en)
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label(__('Quantity'))
                    ->suffix(' EA')
                    ->sortable(),

                TextColumn::make('batch')
                    ->label(__('Batch'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('manufacture_date')
                    ->label(__('Manufacture Date'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('expiration_date')
                    ->label(__('Expiration Date'))
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Add Material'))
                    ->modalHeading(__('Add Material'))
                    ->hidden(fn() => filled($this->getOwnerRecord()?->attp_file))
                    ->after(function () {
                        $this->dispatch('materials-updated');
                    }),

                Action::make('bulkImport')
                    ->label(__('Bulk Import'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->modalHeading(__('Bulk Import Materials'))
                    ->modalDescription(__('Upload a CSV or Excel file with columns: gtin_code, batch, manufacture_date, expiration_date, quantity.'))
                    ->hidden(fn() => filled($this->getOwnerRecord()?->attp_file))
                    ->schema([
                        FileUpload::make('file')
                            ->label(__('Spreadsheet File'))
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->required()
                            ->disk('local')
                            ->directory('temp-imports')
                            ->preserveFilenames(),
                    ])
                    ->extraModalFooterActions([
                        Action::make('downloadTemplate')
                            ->label(__('Download Sample Template'))
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('gray')
                            ->action(function () {
                                $csvContent = "gtin_code,batch,manufacture_date,expiration_date,quantity\n6285000000001,BATCH01,01/08/2026,01/08/2027,100\n";

                                return response()->streamDownload(function () use ($csvContent) {
                                    echo $csvContent;
                                }, 'draft_inbound_materials_template.csv', [
                                    'Content-Type' => 'text/csv',
                                ]);
                            }),
                    ])
                    ->action(function (array $data) {
                        $filePath = Storage::disk('local')->path($data['file']);

                        $importer = new DraftInboundMaterialImporter(
                            $this->getOwnerRecord(),
                            $this->getContractAreas()
                        );

                        $result = $importer->import($filePath);

                        // Delete uploaded temp file
                        @unlink($filePath);

                        $this->dispatch('materials-updated');

                        if ($result['message']) {
                            Notification::make()
                                ->title(__('Import Error'))
                                ->body($result['message'])
                                ->danger()
                                ->send();

                            return;
                        }

                        $successCount = $result['success_count'];
                        $failCount = $result['fail_count'];

                        if ($failCount > 0) {
                            Notification::make()
                                ->title(__('Import Completed with Errors'))
                                ->body(__(':success imported successfully, :failed failed. The error report has been downloaded.', [
                                    'success' => $successCount . ' ' . str('material')->plural($successCount),
                                    'failed' => $failCount . ' ' . str('row')->plural($failCount),
                                ]))
                                ->warning()
                                ->send();

                            if ($result['failed_file_path'] && file_exists($result['failed_file_path'])) {
                                return response()->download($result['failed_file_path'], 'failed_materials_import_' . now()->format('Ymd_His') . '.csv')
                                    ->deleteFileAfterSend(true);
                            }
                        } else {
                            Notification::make()
                                ->title(__('Import Successful'))
                                ->body(__(':count materials have been successfully imported.', ['count' => $successCount]))
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading(__('Edit Material'))
                    ->hidden(fn() => filled($this->getOwnerRecord()?->attp_file))
                    ->after(function () {
                        $this->dispatch('materials-updated');
                    }),
                DeleteAction::make()
                    ->hidden(fn() => filled($this->getOwnerRecord()?->attp_file))
                    ->after(function () {
                        $this->dispatch('materials-updated');
                    }),
            ]);
    }
}
