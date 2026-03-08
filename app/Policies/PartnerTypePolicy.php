<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PartnerType;
use Illuminate\Auth\Access\HandlesAuthorization;

class PartnerTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PartnerType');
    }

    public function view(AuthUser $authUser, PartnerType $partnerType): bool
    {
        return $authUser->can('View:PartnerType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PartnerType');
    }

    public function update(AuthUser $authUser, PartnerType $partnerType): bool
    {
        return $authUser->can('Update:PartnerType');
    }

    public function delete(AuthUser $authUser, PartnerType $partnerType): bool
    {
        return $authUser->can('Delete:PartnerType');
    }

    public function restore(AuthUser $authUser, PartnerType $partnerType): bool
    {
        return $authUser->can('Restore:PartnerType');
    }

    public function forceDelete(AuthUser $authUser, PartnerType $partnerType): bool
    {
        return $authUser->can('ForceDelete:PartnerType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PartnerType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PartnerType');
    }

    public function replicate(AuthUser $authUser, PartnerType $partnerType): bool
    {
        return $authUser->can('Replicate:PartnerType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PartnerType');
    }

}