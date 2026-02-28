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



    protected static function booted()
    {
        static::created(
            function ($partner) {
                // if (config('sap_integration_status')) {
                $partner->refresh();
                // dd($partner);
                $sapService = new \App\Services\SapService();
                $response = $sapService->createPartner(
                    $request_id = (string) $partner->request_id,
                    $request_type = $partner->type->value,
                    $partner_group_code = $partner->partnerType->grouping_code,
                    $name1 = $partner->name1,
                    $name2 = $partner->name2,
                    $gln_loc_no_1 = $partner->gln1,
                    $gln_loc_no_2 = $partner->gln2,
                    $gln_check_digit = $partner->ckgdigit,
                    $city = $partner->city,
                    $street = $partner->street,
                    $house_no = $partner->building,
                    $postal_code = $partner->postal_code,
                    $country_code = $partner->country->code,
                    $region_code = $partner->region->code,
                    $tax_number = $partner->tax_number,
                    $commercial_registration_number = $partner->registration_number,
                    $industry_code = $partner->industry->code,
                    $customer_type_code = $partner->partnerType->customerType->code,
                    $tax_category = $partner->partnerType->tax_cat->value,
                    $mobile = $partner->mobile,
                    $email = $partner->email
                );
                Log::info($response);

                try {
                    if (!empty($partner->tax_card)) {
                        $taxCardResponse = $sapService->uploadFile($partner->request_id . '#TAXCARD', $partner->tax_card);
                        Log::info($taxCardResponse);
                    }
                    if (!empty($partner->commercial_registration)) {
                        $comCardResponse = $sapService->uploadFile($partner->request_id . '#COMCARD', $partner->commercial_registration);
                        Log::info($comCardResponse);
                    }
                    Log::info('SAP File Upload Successfully for partner: ' . $partner->request_id);
                } catch (\Exception $e) {
                    Log::error('SAP File Upload Failed: ' . $e->getMessage());
                }
                // dd($response);
            }
            // }
        );
    }

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
