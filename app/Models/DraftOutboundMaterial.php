<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftOutboundMaterial extends Model
{
    protected $table = 'draft_outbound_material';

    protected $fillable = ['draft_outbound_id', 'material_code', 'quantity', 'max_quantity', 'inbound_request_id'];

    public function draftOutbound(): BelongsTo
    {
        return $this->belongsTo(DraftOutbound::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_code', 'code');
    }
}
