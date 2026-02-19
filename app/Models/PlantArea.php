<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantArea extends Model
{
    protected $fillable = ['name_en', 'name_ar', 'code', 'plant_id'];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'material_area');
    }
}
