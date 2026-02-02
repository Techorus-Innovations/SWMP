<?php

namespace App\Models;

use App\Enums\BusinessPartnerTypeEnum;
use App\Enums\TaxCategoryEnum;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessPartner extends Model
{
    use HasUlids;

    protected $table = 'business_partners';

    protected $fillable = [
        'user_id',
        'request_id',
        'type',
        'partner_type_id',
        'gln1',
        'gln2',
        'ckgdigit',
        'name1',
        'name2',
        'city',
        'street',
        'building',
        'postal_code',
        'country_id',
        'region_id',
        'registration_number',
        'tax_number',
        'industry_id',
        'customer_type_id',
        'tax_category',
        'mobile',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => BusinessPartnerTypeEnum::class,
            'tax_category' => TaxCategoryEnum::class,
            'request_id' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function partnerType(): BelongsTo
    {
        return $this->belongsTo(PartnerType::class, 'partner_type_id');
    }
}
