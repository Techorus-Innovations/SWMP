<?php

namespace App\Models;

use App\Enums\BusinessPartnerTypeEnum;
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
        'name',
        'location',
        'phone_number',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => BusinessPartnerTypeEnum::class,
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
}
