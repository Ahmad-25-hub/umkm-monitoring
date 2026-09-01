<?php

namespace App\Actions;

use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JoinBusinessByInvitationCodeAction
{
    public function __construct(
        private FindUsableBusinessInvitationCodeForUpdateAction $findInvitationCode,
    ) {}

    public function execute(User $user, string $code): BusinessMembership
    {
        return DB::transaction(function () use ($user, $code): BusinessMembership {
            $invitation = $this->findInvitationCode->execute($code);

            $membership = BusinessMembership::query()
                ->where('business_id', $invitation->business_id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($membership !== null) {
                if ($membership->role !== BusinessMembership::ROLE_EMPLOYEE) {
                    throw ValidationException::withMessages([
                        'business_code' => 'Kode Usaha tidak valid atau sudah tidak berlaku.',
                    ]);
                }

                if ($membership->status !== BusinessMembership::STATUS_ACTIVE) {
                    $membership->update(['status' => BusinessMembership::STATUS_ACTIVE]);
                }

                return $membership;
            }

            $membership = BusinessMembership::query()->create([
                'business_id' => $invitation->business_id,
                'user_id' => $user->id,
                'role' => BusinessMembership::ROLE_EMPLOYEE,
                'status' => BusinessMembership::STATUS_ACTIVE,
            ]);

            $invitation->increment('uses_count');

            return $membership;
        }, 3);
    }
}
