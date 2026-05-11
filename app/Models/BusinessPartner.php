<?php

namespace App\Models;

use App\Enums\BusinessPartnerTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

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
        // 'customer_type_id',
        'mobile',
        'email',
        'commercial_registration',
        'tax_card',
        'status',
        'bp_code',
    ];

    public function submitToSap()
    {
        // $this->refresh();
        $sapService = new \App\Services\SapService;
        $response = $sapService->createPartner(
            $request_id = (string) $this->request_id,
            $request_type = $this->type->value,
            $partner_group_code = $this->partnerType->grouping_code,
            $name1 = $this->name1,
            $name2 = $this->name2,
            $gln_loc_no_1 = $this->gln1,
            $gln_loc_no_2 = $this->gln2,
            $gln_check_digit = $this->ckgdigit,
            $city = $this->city,
            $street = $this->street,
            $house_no = $this->building,
            $postal_code = $this->postal_code,
            $country_code = $this->country->code,
            $region_code = $this->region->code,
            $tax_number = $this->tax_number,
            $commercial_registration_number = $this->registration_number,
            $industry_code = $this->industry?->code,
            $customer_type_code = $this->partnerType->customerType->code,
            $tax_category = $this->partnerType->tax_cat->value,
            $mobile = $this->mobile,
            $email = $this->email
        );
        Log::info($response);

        \App\Jobs\UploadPartnerFilesToSap::dispatch($this)->afterCommit();
    }


    protected static function booted(): void
    {
        static::created(function (BusinessPartner $partner) {
            $partner->refresh();
            dd($partner);
            $partner->submitToSap();
        });
    }

    public function getname()
    {
        return $this->name1 . $this->name2;
    }

    protected function casts(): array
    {
        return [
            'type' => BusinessPartnerTypeEnum::class,
            // 'request_id' => 'string',
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
