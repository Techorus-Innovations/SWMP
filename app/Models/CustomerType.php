<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerType extends Model
{
    protected $fillable = [
        'code',
        'name_en',
        'name_ar',
    ];

    public function partnerTypes(): HasMany
    {
        return $this->hasMany(PartnerType::class, 'cust_type_id');
    }
}
