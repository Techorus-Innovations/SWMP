<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DraftInbound extends Model
{
    protected $fillable = ['title', 'contract_id', 'user_id', 'attp_file'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'draft_inbound_material')
            ->using(DraftInboundMaterial::class)
            ->withPivot('id', 'quantity', 'batch', 'manufacture_date', 'expiration_date', 'plant_area_id');
    }

    public function inboundMaterials(): HasMany
    {
        return $this->hasMany(DraftInboundMaterial::class, 'draft_inbound_id');
    }

    public function getMaterialsData(): array
    {
        return $this->materials->mapWithKeys(function ($material) {
            return [(string) $material->code => [
                'quantity' => $material->pivot->quantity,
                'batch' => $material->pivot->batch,
                'manufacture_date' => $material->pivot->manufacture_date,
                'expiration_date' => $material->pivot->expiration_date,
                'plant_area_id' => $material->pivot->plant_area_id,
            ]];
        })->toArray();
    }
}

