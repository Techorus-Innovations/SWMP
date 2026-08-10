<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DraftOutbound extends Model
{
    use HasUlids;

    protected $fillable = ['ship_to_party', 'business_partner_id'];

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'draft_outbound_material', 'draft_outbound_id', 'material_code', 'id', 'code')
            ->using(DraftOutboundMaterial::class)
            ->withPivot('quantity', 'max_quantity', 'quantity_unit', 'location_code', 'batch_code', 'expiration_date', 'manufacture_date');
    }

    public function outboundMaterials(): HasMany
    {
        return $this->hasMany(DraftOutboundMaterial::class);
    }

    public function getMaterialsData(): array
    {
        return $this->materials->mapWithKeys(function ($material) {
            return [(string) $material->code => [
                'quantity' => $material->pivot->quantity,
                'max_quantity' => $material->pivot->max_quantity,
                'quantity_unit' => $material->pivot->quantity_unit,
                'location_code' => $material->pivot->location_code,
                'batch_code' => $material->pivot->batch_code,
                'manufacture_date' => $material->pivot->manufacture_date,
                'expiration_date' => $material->pivot->expiration_date,
            ]];
        })->toArray();
    }
}

