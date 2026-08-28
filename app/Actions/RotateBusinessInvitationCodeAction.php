<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use App\Support\InvitationCodeGenerator;
use Illuminate\Support\Facades\DB;

class RotateBusinessInvitationCodeAction
{
    public function __construct(private InvitationCodeGenerator $invitationCodeGenerator) {}

    public function execute(Business $business, User $actor): string
    {
        return DB::transaction(function () use ($business, $actor): string {
            $lockedBusiness = Business::query()
                ->lockForUpdate()
                ->findOrFail($business->id);

            $invitationCode = $this->invitationCodeGenerator->generateUnique();

            $lockedBusiness->invitationCode()->updateOrCreate([], [
                'created_by_user_id' => $actor->id,
                'code_hash' => $this->invitationCodeGenerator->hash($invitationCode),
                'expires_at' => null,
                'max_uses' => null,
                'uses_count' => 0,
                'revoked_at' => null,
            ]);

            return $invitationCode;
        }, attempts: 3);
    }
}
