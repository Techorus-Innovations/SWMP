<?php

namespace App\Services;

use App\Models\DraftInbound;
use App\Models\DraftInboundMaterial;
use App\Models\Material;
use App\Models\PlantArea;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use SplFileObject;

class DraftInboundMaterialImporter
{
    protected DraftInbound $draftInbound;

    protected Collection $contractAreas;

    public function __construct(DraftInbound $draftInbound, Collection $contractAreas)
    {
        $this->draftInbound = $draftInbound;
        $this->contractAreas = $contractAreas;
    }

    public function import(string $filePath): array
    {
        $rows = $this->readFileRows($filePath);

        if (empty($rows)) {
            return [
                'success_count' => 0,
                'fail_count' => 0,
                'failed_file_path' => null,
                'message' => __('Uploaded file is empty or could not be read.'),
            ];
        }

        $headerRow = array_shift($rows);
        $headerMap = $this->buildHeaderMap($headerRow);

        $requiredKeys = ['gtin_code', 'batch', 'manufacture_date', 'expiration_date', 'quantity'];
        $missingKeys = array_diff($requiredKeys, array_values($headerMap));

        if (! empty($missingKeys)) {
            return [
                'success_count' => 0,
                'fail_count' => 0,
                'failed_file_path' => null,
                'message' => __('Missing required column(s): :columns', ['columns' => implode(', ', $missingKeys)]),
            ];
        }

        $validRecords = [];
        $failedRows = [];
        $processedCombinations = [];

        $latestSync = Material::max('sync_at');

        foreach ($rows as $index => $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rowData = $this->extractRowData($row, $headerMap);
            $errors = [];

            // 1. GTIN validation & Material Lookup
            $gtin = trim((string) ($rowData['gtin_code'] ?? ''));
            $material = null;
            $plantArea = null;

            if ($gtin === '') {
                $errors[] = __('GTIN code is required.');
            } else {
                $material = Material::where('gtin_code', $gtin)
                    ->when($latestSync, fn ($q) => $q->where('sync_at', $latestSync))
                    ->first();

                if (! $material) {
                    $material = Material::where('gtin_code', $gtin)->first();
                }

                if (! $material) {
                    $errors[] = __('Material not found.');
                } else {
                    $plantArea = PlantArea::whereIn('code', $this->contractAreas)
                        ->whereHas('materials', fn ($q) => $q->where('materials.id', $material->id))
                        ->first();

                    if (! $plantArea) {
                        $errors[] = __('Material not permitted in contract storage locations.');
                    }
                }
            }

            // 2. Batch validation
            $batch = trim((string) ($rowData['batch'] ?? ''));
            if ($batch === '') {
                $errors[] = __('Batch is required.');
            } elseif (mb_strlen($batch) > 10) {
                $errors[] = __('Batch must not exceed 10 characters.');
            }

            // 3. Quantity validation
            $quantityRaw = $rowData['quantity'] ?? null;
            if ($quantityRaw === null || $quantityRaw === '' || ! is_numeric($quantityRaw)) {
                $errors[] = __('Quantity is required and must be a valid number.');
            } elseif ((int) $quantityRaw < 1) {
                $errors[] = __('Quantity must be at least 1.');
            }
            $quantity = (int) $quantityRaw;

            // 4. Manufacture Date validation
            $mfgDateRaw = $rowData['manufacture_date'] ?? null;
            $mfgDate = $this->parseDate($mfgDateRaw);
            if (! $mfgDate) {
                $errors[] = __('Invalid or missing manufacture date.');
            } elseif ($mfgDate->gt(Carbon::now()->addMonth())) {
                $errors[] = __('Manufacture date cannot be more than 1 month in the future.');
            }

            // 5. Expiration Date validation
            $expDateRaw = $rowData['expiration_date'] ?? null;
            $expDate = $this->parseDate($expDateRaw);
            if (! $expDate) {
                $errors[] = __('Invalid or missing expiration date.');
            } elseif ($mfgDate && $expDate->lte($mfgDate)) {
                $errors[] = __('Expiration date must be after manufacture date.');
            }

            // 6. Duplicate / Uniqueness validation
            if ($material && $plantArea && $batch !== '') {
                $combinationKey = "{$material->id}_{$batch}_{$plantArea->id}";

                if (isset($processedCombinations[$combinationKey])) {
                    $errors[] = __('Same combination of material, batch, and storage location already exists.');
                } else {
                    $existsInDb = DraftInboundMaterial::where('draft_inbound_id', $this->draftInbound->id)
                        ->where('material_id', $material->id)
                        ->where('batch', $batch)
                        ->where('plant_area_id', $plantArea->id)
                        ->exists();

                    if ($existsInDb) {
                        $errors[] = __('Same combination of material, batch, and storage location already exists.');
                    }
                }
            }

            if (! empty($errors)) {
                $failedRows[] = [
                    'gtin_code' => $rowData['gtin_code'] ?? '',
                    'batch' => $rowData['batch'] ?? '',
                    'manufacture_date' => $this->formatDateString($mfgDateRaw),
                    'expiration_date' => $this->formatDateString($expDateRaw),
                    'quantity' => $rowData['quantity'] ?? '',
                    'error_reason' => implode(' | ', $errors),
                ];
            } else {
                $combinationKey = "{$material->id}_{$batch}_{$plantArea->id}";
                $processedCombinations[$combinationKey] = true;

                $validRecords[] = [
                    'draft_inbound_id' => $this->draftInbound->id,
                    'material_id' => $material->id,
                    'plant_area_id' => $plantArea->id,
                    'quantity' => $quantity,
                    'batch' => $batch,
                    'manufacture_date' => $mfgDate->format('Y-m-d'),
                    'expiration_date' => $expDate->format('Y-m-d'),
                ];
            }
        }

        // Insert valid records
        if (! empty($validRecords)) {
            DraftInboundMaterial::insert($validRecords);
        }

        $failedFilePath = null;
        if (! empty($failedRows)) {
            $failedFilePath = $this->generateFailedSheet($failedRows);
        }

        return [
            'success_count' => count($validRecords),
            'fail_count' => count($failedRows),
            'failed_file_path' => $failedFilePath,
            'message' => null,
        ];
    }

    protected function buildHeaderMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized) {
                $map[$index] = $normalized;
            }
        }

        return $map;
    }

    protected function normalizeHeader(string $header): string
    {
        $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $header)));

        return match ($clean) {
            'gtincode', 'gitncode', 'gtin', 'gitn', 'barcode' => 'gtin_code',
            'batch', 'batchcode', 'batchnumber', 'lot', 'lotnumber' => 'batch',
            'manufacturedate', 'mfgdate', 'mfg', 'productiondate' => 'manufacture_date',
            'expirationdate', 'expirydate', 'expdate', 'expiry' => 'expiration_date',
            'quantity', 'qty', 'amount', 'count' => 'quantity',
            default => $clean,
        };
    }

    protected function extractRowData(array $row, array $headerMap): array
    {
        $data = [];
        foreach ($headerMap as $index => $key) {
            $data[$key] = $row[$index] ?? null;
        }

        return $data;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_numeric($value) && (int) $value > 30000 && (int) $value < 70000) {
            return Carbon::createFromTimestampUTC((((int) $value) - 25569) * 86400);
        }

        $stringVal = trim((string) $value);

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'd/m/y', 'd-m-y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $stringVal);
                if ($date !== false) {
                    return $date;
                }
            } catch (\Throwable $e) {
                // Continue to next format
            }
        }

        try {
            return Carbon::parse($stringVal);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function formatDateString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        $parsed = $this->parseDate($value);
        if ($parsed) {
            return $parsed->format('d/m/Y');
        }

        return (string) ($value ?? '');
    }

    protected function readFileRows(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv' || $extension === 'txt') {
            return $this->readCsvRows($filePath);
        }

        try {
            $reader = (new ReaderFactory())->createFromFileByExtension($filePath);
            $reader->open($filePath);

            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = [];
                    foreach ($row->getCells() as $cell) {
                        $cells[] = $cell->getValue();
                    }
                    $rows[] = $cells;
                }
                break; // Only read the first sheet
            }
            $reader->close();

            return $rows;
        } catch (\Throwable $e) {
            // Fallback to CSV reader
            return $this->readCsvRows($filePath);
        }
    }

    protected function readCsvRows(string $filePath): array
    {
        $rows = [];
        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        foreach ($file as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    protected function generateFailedSheet(array $failedRows): string
    {
        $dir = storage_path('app/temp');
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $fileName = 'failed_materials_import_' . now()->format('Ymd_His') . '.csv';
        $fullPath = $dir . '/' . $fileName;

        $fp = fopen($fullPath, 'w');
        // Add BOM for UTF-8 Excel support
        fputs($fp, "\xEF\xBB\xBF");

        fputcsv($fp, ['gtin_code', 'batch', 'manufacture_date', 'expiration_date', 'quantity', 'error_reason']);

        foreach ($failedRows as $row) {
            fputcsv($fp, [
                $row['gtin_code'],
                $row['batch'],
                $row['manufacture_date'],
                $row['expiration_date'],
                $row['quantity'],
                $row['error_reason'],
            ]);
        }

        fclose($fp);

        return $fullPath;
    }
}
