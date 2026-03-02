<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DraftInbound extends Model
{
    protected $fillable = ['title', 'contract_id', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'draft_inbound_material')->withPivot('quantity');
    }

    public function getMaterialCodesWithQuantity() {
        return $this->materials->mapWithKeys(function ($material) {
            return [(String) $material->code => $material->pivot->quantity];
        })->toArray();
        
    }
}
