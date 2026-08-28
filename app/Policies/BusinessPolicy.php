<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BusinessPolicy
{
    public function view(User $user, Business $business): bool
    {
        return $business->memberships()
            ->whereBelongsTo($user)
            ->active()
            ->exists();
    }

    public function rotateInvitationCode(User $user, Business $business): Response
    {
        return $this->activeOwnerResponse($user, $business);
    }

    public function activateForOwner(User $user, Business $business): Response
    {
        return $this->activeOwnerResponse($user, $business);
    }

    private function activeOwnerResponse(User $user, Business $business): Response
    {
        $isActiveOwner = $business->memberships()
            ->whereBelongsTo($user)
            ->where('role', BusinessMembership::ROLE_OWNER)
            ->active()
            ->exists();

        return $isActiveOwner
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
