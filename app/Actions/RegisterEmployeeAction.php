<?php

namespace App\Actions;

use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterEmployeeAction
{
    public function __construct(
        private FindUsableBusinessInvitationCodeForUpdateAction $findInvitationCode,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, business_code: string}  $attributes
     * @return array{user: User, membership: BusinessMembership}
     */
    public function execute(array $attributes): array
    {
        return DB::transaction(function () use ($attributes): array {
            $invitation = $this->findInvitationCode->execute($attributes['business_code']);

            $user = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
            ]);

            $membership = BusinessMembership::query()->create([
                'business_id' => $invitation->business_id,
                'user_id' => $user->id,
                'role' => BusinessMembership::ROLE_EMPLOYEE,
                'status' => BusinessMembership::STATUS_ACTIVE,
            ]);

            $invitation->increment('uses_count');

            return [
                'user' => $user,
                'membership' => $membership,
            ];
        }, attempts: 3);
    }
}
