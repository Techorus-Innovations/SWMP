<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'code',
        'name_en',
        'name_ar',
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }
}
