<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\MaterialGroup;
use App\Models\MaterialType;
use App\Models\Plant;
use App\Models\PlantArea;
use App\Services\SapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMaterialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'materials:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync materials from SAP system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $sapService = new SapService();
            $materials = $sapService->getMaterials();

            if (empty($materials)) {
                $this->warn('No materials found from SAP');
                return Command::SUCCESS;
            }

            $this->info('Starting materials sync...');
            $syncedCount = 0;
            $failedCount = 0;
            $syncTime = now();

            foreach ($materials as $materialData) {
                try {
                    $this->syncMaterial($materialData, $syncTime);
                    $syncedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Failed to sync material', [
                        'material_code' => $materialData['material_code'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("Failed to sync material {$materialData['material_code']}: {$e->getMessage()}");
                }
            }

            $this->info("Materials sync completed! Synced: {$syncedCount}, Failed: {$failedCount}");
            Log::info('Materials sync completed', ['synced' => $syncedCount, 'failed' => $failedCount]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Materials sync failed', ['error' => $e->getMessage()]);
            $this->error('Materials sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Sync a single material with all its relationships
     */
    private function syncMaterial(array $materialData, \Carbon\Carbon $syncTime): void
    {
        // Find or create material type
        $materialType = MaterialType::firstOrCreate(
            ['code' => $materialData['material_type_code']],
            [
                'name_en' => $materialData['material_type_name'],
                'name_ar' => $materialData['material_type_name'], // Using same name for both languages
            ]
        );

        // Find or create material group
        $materialGroup = MaterialGroup::firstOrCreate(
            ['code' => $materialData['material_group_code']],
            [
                'name_en' => $materialData['material_group_name'],
                'name_ar' => $materialData['material_group_name'], // Using same name for both languages
            ]
        );

        // Find or create material
        $material = Material::updateOrCreate(
            ['code' => $materialData['material_code']],
            [
                'name_en' => $materialData['material_name'],
                'name_ar' => $materialData['material_name'], // Using same name for both languages
                'gtin_code' => $materialData['material_GTIN_code'],
                'material_type_id' => $materialType->id,
                'material_group_id' => $materialGroup->id,
                'sync_at' => $syncTime,
                'attp_required' => $materialData['attp_required']
            ]
        );

        // Process plants and areas
        $areaIds = [];
        foreach ($materialData['plants'] as $plantData) {
            // Find or create plant
            $plant = Plant::updateOrCreate(
                ['code' => $plantData['plant_code']],
                [
                    'name_en' => $plantData['plant_name'],
                    'name_ar' => $plantData['plant_name'], // Using same name for both languages
                ]
            );

            // Process areas within this plant
            foreach ($plantData['areas'] as $areaData) {
                // Find or create plant area
                $plantArea = PlantArea::updateOrCreate(
                    [
                        'code' => $areaData['area_code'],
                        'plant_id' => $plant->id,
                    ],
                    [
                        'name_en' => $areaData['area_name'],
                        'name_ar' => $areaData['area_name'], // Using same name for both languages
                    ]
                );

                $areaIds[] = $plantArea->id;
            }
        }

        // Sync material areas (attach only the areas from this sync)
        if (!empty($areaIds)) {
            $material->areas()->sync($areaIds);
        } else {
            // If no areas, detach all areas
            $material->areas()->detach();
        }
    }
}
