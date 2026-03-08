<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CustomerType;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerType');
    }

    public function view(AuthUser $authUser, CustomerType $customerType): bool
    {
        return $authUser->can('View:CustomerType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerType');
    }

    public function update(AuthUser $authUser, CustomerType $customerType): bool
    {
        return $authUser->can('Update:CustomerType');
    }

    public function delete(AuthUser $authUser, CustomerType $customerType): bool
    {
        return $authUser->can('Delete:CustomerType');
    }

    public function restore(AuthUser $authUser, CustomerType $customerType): bool
    {
        return $authUser->can('Restore:CustomerType');
    }

    public function forceDelete(AuthUser $authUser, CustomerType $customerType): bool
    {
        return $authUser->can('ForceDelete:CustomerType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomerType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomerType');
    }

    public function replicate(AuthUser $authUser, CustomerType $customerType): bool
    {
        return $authUser->can('Replicate:CustomerType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomerType');
    }

}