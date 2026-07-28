<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DraftInboundMaterial extends Pivot
{
    protected $table = 'draft_inbound_material';

    public function plantArea()
    {
        return $this->belongsTo(PlantArea::class, 'plant_area_id');
    }
}
