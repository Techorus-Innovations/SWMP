<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftOutboundMaterial extends Model
{
    protected $table = 'draft_outbound_material';

    protected $fillable = ['draft_outbound_id', 'material_code', 'quantity', 'max_quantity', 'quantity_unit', 'location_code', 'batch_code', 'expiration_date', 'manufacture_date'];

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
}
