<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MaterialGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaterialGroupPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MaterialGroup');
    }

    public function view(AuthUser $authUser, MaterialGroup $materialGroup): bool
    {
        return $authUser->can('View:MaterialGroup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MaterialGroup');
    }

    public function update(AuthUser $authUser, MaterialGroup $materialGroup): bool
    {
        return $authUser->can('Update:MaterialGroup');
    }

    public function delete(AuthUser $authUser, MaterialGroup $materialGroup): bool
    {
        return $authUser->can('Delete:MaterialGroup');
    }

    public function restore(AuthUser $authUser, MaterialGroup $materialGroup): bool
    {
        return $authUser->can('Restore:MaterialGroup');
    }

    public function forceDelete(AuthUser $authUser, MaterialGroup $materialGroup): bool
    {
        return $authUser->can('ForceDelete:MaterialGroup');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MaterialGroup');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MaterialGroup');
    }

    public function replicate(AuthUser $authUser, MaterialGroup $materialGroup): bool
    {
        return $authUser->can('Replicate:MaterialGroup');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MaterialGroup');
    }

}