<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialType extends Model
{
    protected $fillable = ['name_en', 'name_ar', 'code'];

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}
