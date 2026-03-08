<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Plant;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlantPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Plant');
    }

    public function view(AuthUser $authUser, Plant $plant): bool
    {
        return $authUser->can('View:Plant');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Plant');
    }

    public function update(AuthUser $authUser, Plant $plant): bool
    {
        return $authUser->can('Update:Plant');
    }

    public function delete(AuthUser $authUser, Plant $plant): bool
    {
        return $authUser->can('Delete:Plant');
    }

    public function restore(AuthUser $authUser, Plant $plant): bool
    {
        return $authUser->can('Restore:Plant');
    }

    public function forceDelete(AuthUser $authUser, Plant $plant): bool
    {
        return $authUser->can('ForceDelete:Plant');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Plant');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Plant');
    }

    public function replicate(AuthUser $authUser, Plant $plant): bool
    {
        return $authUser->can('Replicate:Plant');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Plant');
    }

}