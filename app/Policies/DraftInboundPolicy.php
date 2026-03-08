<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DraftInbound;
use Illuminate\Auth\Access\HandlesAuthorization;

class DraftInboundPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DraftInbound');
    }

    public function view(AuthUser $authUser, DraftInbound $draftInbound): bool
    {
        return $authUser->can('View:DraftInbound');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DraftInbound');
    }

    public function update(AuthUser $authUser, DraftInbound $draftInbound): bool
    {
        return $authUser->can('Update:DraftInbound');
    }

    public function delete(AuthUser $authUser, DraftInbound $draftInbound): bool
    {
        return $authUser->can('Delete:DraftInbound');
    }

    public function restore(AuthUser $authUser, DraftInbound $draftInbound): bool
    {
        return $authUser->can('Restore:DraftInbound');
    }

    public function forceDelete(AuthUser $authUser, DraftInbound $draftInbound): bool
    {
        return $authUser->can('ForceDelete:DraftInbound');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DraftInbound');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DraftInbound');
    }

    public function replicate(AuthUser $authUser, DraftInbound $draftInbound): bool
    {
        return $authUser->can('Replicate:DraftInbound');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DraftInbound');
    }

}