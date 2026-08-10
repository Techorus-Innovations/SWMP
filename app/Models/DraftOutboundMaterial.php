<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DraftOutboundMaterial extends Pivot
{
    protected $table = 'draft_outbound_material';

    protected $fillable = ['draft_outbound_id', 'material_code', 'quantity', 'max_quantity', 'quantity_unit', 'location_code', 'batch_code', 'expiration_date', 'manufacture_date'];

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'manufacture_date' => 'date',
            'expiration_date' => 'date',
        ];
    }
    public function draftOutbound(): BelongsTo
    {
        return $this->belongsTo(DraftOutbound::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_code', 'code');
    }

    public function plantArea(): BelongsTo
    {
        return $this->belongsTo(PlantArea::class, 'location_code', 'code');
    }
}


