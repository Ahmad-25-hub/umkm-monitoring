<?php

namespace App\Policies;

use App\Models\BusinessMembership;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskOccurrencePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->memberships()->active()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TaskOccurrence $taskOccurrence): Response
    {
        if ($taskOccurrence->user_id === $user->id && $this->hasActiveEmployeeMembership($user, $taskOccurrence)) {
            return Response::allow();
        }

        return $this->activeOwnerResponse($user, $taskOccurrence);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TaskOccurrence $taskOccurrence): bool
    {
        return false;
    }

    public function updateProgress(User $user, TaskOccurrence $taskOccurrence): Response
    {
        $isAllowed = $taskOccurrence->user_id === $user->id
            && $this->hasActiveEmployeeMembership($user, $taskOccurrence);

        return $isAllowed
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function cancel(User $user, TaskOccurrence $taskOccurrence): Response
    {
        return $this->activeOwnerResponse($user, $taskOccurrence);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TaskOccurrence $taskOccurrence): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TaskOccurrence $taskOccurrence): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TaskOccurrence $taskOccurrence): bool
    {
        return false;
    }

    private function hasActiveEmployeeMembership(User $user, TaskOccurrence $taskOccurrence): bool
    {
        return $user->memberships()
            ->where('business_id', $taskOccurrence->task()->value('business_id'))
            ->where('role', BusinessMembership::ROLE_EMPLOYEE)
            ->active()
            ->exists();
    }

    private function activeOwnerResponse(User $user, TaskOccurrence $taskOccurrence): Response
    {
        $isActiveOwner = $user->memberships()
            ->where('business_id', $taskOccurrence->task()->value('business_id'))
            ->where('role', BusinessMembership::ROLE_OWNER)
            ->active()
            ->exists();

        return $isActiveOwner
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
