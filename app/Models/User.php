<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserTypeEnum;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUlids, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'business_partner_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'type' => UserTypeEnum::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->type === UserTypeEnum::Admin;
        }
        else {
            return $this->type !== UserTypeEnum::Admin;
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class);
    }

    public function partnerRoles(): BelongsToMany
    {
        return $this->belongsToMany(PartnerRole::class);
    }

    public function canCreateInbounds(): bool
    {
        return $this->partnerRoles()->where('name', 'create_inbounds')->exists();
    }

    public function canViewInbounds(): bool
    {
        return $this->partnerRoles()->where('name', 'view_inbounds')->exists();
    }

    public function canCancelInbounds(): bool
    {
        return $this->partnerRoles()->where('name', 'cancel_inbounds')->exists();
    }

    public function canManageUsers(): bool
    {
        return $this->partnerRoles()->where('name', 'manage_users')->exists();
    }

    public function canViewStock(): bool
    {
        return $this->partnerRoles()->where('name', 'view_stock')->exists();
    }

    public function canCreateOutbounds(): bool
    {
        return $this->partnerRoles()->where('name', 'create_outbounds')->exists();
    }

    public function canViewOutbounds(): bool
    {
        return $this->partnerRoles()->where('name', 'view_outbounds')->exists();
    }

    public function canCancelOutbounds(): bool
    {
        return $this->partnerRoles()->where('name', 'cancel_outbounds')->exists();
    }
}
