<?php

namespace App\Models;

use App\Models\PlantArea;
use Illuminate\Database\Eloquent\Model;

class DraftInbound extends Model
{
    protected $fillable = ['title', 'contract_id', 'user_id', 'attp_file'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'draft_inbound_material')
            ->using(DraftInboundMaterial::class)
            ->withPivot('quantity', 'batch', 'manufacture_date', 'expiration_date', 'plant_area_id');
    }

    public function getMaterialsData() {
        return $this->materials->mapWithKeys(function ($material) {
            return [(String) $material->code => [
                'quantity' => $material->pivot->quantity,
                'batch' => $material->pivot->batch,
                'manufacture_date' => $material->pivot->manufacture_date,
                'expiration_date' => $material->pivot->expiration_date,
                'plant_area_id' => $material->pivot->plant_area_id,
            ]];
        })->toArray();
    }
}
