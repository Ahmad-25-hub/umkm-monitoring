<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use App\Support\InvitationCodeGenerator;

class RegisterOwnerAction
{
    public function __construct(private InvitationCodeGenerator $invitationCodeGenerator) {}

    /**
     * @param  array{owner_name: string, business_name: string, email: string, password: string}  $attributes
     * @return array{user: User, business: Business, invitation_code: string}
     */
    public function execute(array $attributes): array
    {
        $user = User::query()->create([
            'name' => $attributes['owner_name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
        ]);

        $business = Business::query()->create([
            'name' => $attributes['business_name'],
        ]);

        $business->memberships()->create([
            'user_id' => $user->id,
            'role' => BusinessMembership::ROLE_OWNER,
            'status' => BusinessMembership::STATUS_ACTIVE,
        ]);

        $invitationCode = $this->invitationCodeGenerator->generateUnique();

        $business->invitationCode()->create([
            'created_by_user_id' => $user->id,
            'code_hash' => $this->invitationCodeGenerator->hash($invitationCode),
        ]);

        return [
            'user' => $user,
            'business' => $business,
            'invitation_code' => $invitationCode,
        ];
    }
}
