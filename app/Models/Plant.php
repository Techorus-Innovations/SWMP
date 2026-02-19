<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $fillable = ['name_en', 'name_ar', 'code'];

    public function areas()
    {
        return $this->hasMany(PlantArea::class);
    }
}
