<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PlantArea;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlantAreaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PlantArea');
    }

    public function view(AuthUser $authUser, PlantArea $plantArea): bool
    {
        return $authUser->can('View:PlantArea');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PlantArea');
    }

    public function update(AuthUser $authUser, PlantArea $plantArea): bool
    {
        return $authUser->can('Update:PlantArea');
    }

    public function delete(AuthUser $authUser, PlantArea $plantArea): bool
    {
        return $authUser->can('Delete:PlantArea');
    }

    public function restore(AuthUser $authUser, PlantArea $plantArea): bool
    {
        return $authUser->can('Restore:PlantArea');
    }

    public function forceDelete(AuthUser $authUser, PlantArea $plantArea): bool
    {
        return $authUser->can('ForceDelete:PlantArea');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PlantArea');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PlantArea');
    }

    public function replicate(AuthUser $authUser, PlantArea $plantArea): bool
    {
        return $authUser->can('Replicate:PlantArea');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PlantArea');
    }

}