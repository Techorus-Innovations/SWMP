<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DraftInboundMaterial extends Pivot
{
    protected $table = 'draft_inbound_material';

    protected $fillable = [
        'draft_inbound_id',
        'material_id',
        'plant_area_id',
        'quantity',
        'batch',
        'manufacture_date',
        'expiration_date',
    ];

    public $incrementing = true;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'manufacture_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    public function draftInbound(): BelongsTo
    {
        return $this->belongsTo(DraftInbound::class, 'draft_inbound_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function plantArea(): BelongsTo
    {
        return $this->belongsTo(PlantArea::class, 'plant_area_id');
    }
}

