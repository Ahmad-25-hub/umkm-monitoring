<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasActiveOwnedBusiness();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): Response
    {
        return $this->activeOwnerResponse($user, $task->business_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasActiveOwnedBusiness();
    }

    public function createForBusiness(User $user, Business $business): Response
    {
        return $this->activeOwnerResponse($user, $business->id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): Response
    {
        return $this->activeOwnerResponse($user, $task->business_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }

    private function activeOwnerResponse(User $user, int $businessId): Response
    {
        $isActiveOwner = $user->memberships()
            ->where('business_id', $businessId)
            ->where('role', BusinessMembership::ROLE_OWNER)
            ->active()
            ->exists();

        return $isActiveOwner
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
