<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PartnerRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class PartnerRolePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PartnerRole');
    }

    public function view(AuthUser $authUser, PartnerRole $partnerRole): bool
    {
        return $authUser->can('View:PartnerRole');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PartnerRole');
    }

    public function update(AuthUser $authUser, PartnerRole $partnerRole): bool
    {
        return $authUser->can('Update:PartnerRole');
    }

    public function delete(AuthUser $authUser, PartnerRole $partnerRole): bool
    {
        return $authUser->can('Delete:PartnerRole');
    }

    public function restore(AuthUser $authUser, PartnerRole $partnerRole): bool
    {
        return $authUser->can('Restore:PartnerRole');
    }

    public function forceDelete(AuthUser $authUser, PartnerRole $partnerRole): bool
    {
        return $authUser->can('ForceDelete:PartnerRole');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PartnerRole');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PartnerRole');
    }

    public function replicate(AuthUser $authUser, PartnerRole $partnerRole): bool
    {
        return $authUser->can('Replicate:PartnerRole');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PartnerRole');
    }

}