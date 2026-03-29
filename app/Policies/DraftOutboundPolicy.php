<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DraftOutbound;
use Illuminate\Auth\Access\HandlesAuthorization;

class DraftOutboundPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DraftOutbound');
    }

    public function view(AuthUser $authUser, DraftOutbound $draftOutbound): bool
    {
        return $authUser->can('View:DraftOutbound');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DraftOutbound');
    }

    public function update(AuthUser $authUser, DraftOutbound $draftOutbound): bool
    {
        return $authUser->can('Update:DraftOutbound');
    }

    public function delete(AuthUser $authUser, DraftOutbound $draftOutbound): bool
    {
        return $authUser->can('Delete:DraftOutbound');
    }

    public function restore(AuthUser $authUser, DraftOutbound $draftOutbound): bool
    {
        return $authUser->can('Restore:DraftOutbound');
    }

    public function forceDelete(AuthUser $authUser, DraftOutbound $draftOutbound): bool
    {
        return $authUser->can('ForceDelete:DraftOutbound');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DraftOutbound');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DraftOutbound');
    }

    public function replicate(AuthUser $authUser, DraftOutbound $draftOutbound): bool
    {
        return $authUser->can('Replicate:DraftOutbound');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DraftOutbound');
    }

}