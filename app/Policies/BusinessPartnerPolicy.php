<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BusinessPartner;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessPartnerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BusinessPartner');
    }

    public function view(AuthUser $authUser, BusinessPartner $businessPartner): bool
    {
        return $authUser->can('View:BusinessPartner');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BusinessPartner');
    }

    public function update(AuthUser $authUser, BusinessPartner $businessPartner): bool
    {
        return $authUser->can('Update:BusinessPartner');
    }

    public function delete(AuthUser $authUser, BusinessPartner $businessPartner): bool
    {
        return $authUser->can('Delete:BusinessPartner');
    }

    public function restore(AuthUser $authUser, BusinessPartner $businessPartner): bool
    {
        return $authUser->can('Restore:BusinessPartner');
    }

    public function forceDelete(AuthUser $authUser, BusinessPartner $businessPartner): bool
    {
        return $authUser->can('ForceDelete:BusinessPartner');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BusinessPartner');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BusinessPartner');
    }

    public function replicate(AuthUser $authUser, BusinessPartner $businessPartner): bool
    {
        return $authUser->can('Replicate:BusinessPartner');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BusinessPartner');
    }

}