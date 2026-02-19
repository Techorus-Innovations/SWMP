<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = ['name_en', 'name_ar', 'code', 'material_type_id', 'material_group_id'];

    public function materialType()
    {
        return $this->belongsTo(MaterialType::class);
    }

    public function materialGroup()
    {
        return $this->belongsTo(MaterialGroup::class);
    }

    public function areas()
    {
        return $this->belongsToMany(PlantArea::class, 'material_area');
    }
}
