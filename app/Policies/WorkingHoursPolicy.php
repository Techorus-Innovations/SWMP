<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WorkingHours;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkingHoursPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WorkingHours');
    }

    public function view(AuthUser $authUser, WorkingHours $workingHours): bool
    {
        return $authUser->can('View:WorkingHours');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WorkingHours');
    }

    public function update(AuthUser $authUser, WorkingHours $workingHours): bool
    {
        return $authUser->can('Update:WorkingHours');
    }

    public function delete(AuthUser $authUser, WorkingHours $workingHours): bool
    {
        return $authUser->can('Delete:WorkingHours');
    }

    public function restore(AuthUser $authUser, WorkingHours $workingHours): bool
    {
        return $authUser->can('Restore:WorkingHours');
    }

    public function forceDelete(AuthUser $authUser, WorkingHours $workingHours): bool
    {
        return $authUser->can('ForceDelete:WorkingHours');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WorkingHours');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WorkingHours');
    }

    public function replicate(AuthUser $authUser, WorkingHours $workingHours): bool
    {
        return $authUser->can('Replicate:WorkingHours');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WorkingHours');
    }

}