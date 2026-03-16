<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DraftOutbound extends Model
{
    use HasUlids;

    protected $fillable = ['ship_to_party', 'business_partner_id'];

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(DraftOutboundMaterial::class);
    }
}
