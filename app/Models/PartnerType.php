<?php

namespace App\Models;

use App\Enums\PartnerTypeEnum;
use App\Enums\TaxCategoryEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerType extends Model
{
    protected $fillable = [
        'grouping_code',
        'name_en',
        'name_ar',
        'type',
        'cust_type_id',
        'tax_cat',
    ];

    protected $casts = [
        'type' => PartnerTypeEnum::class,
        'tax_cat' => TaxCategoryEnum::class,
    ];

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'cust_type_id');
    }
}
