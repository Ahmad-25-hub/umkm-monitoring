<?php

namespace App\Actions;

use App\Models\BusinessInvitationCode;
use App\Support\InvitationCodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class FindUsableBusinessInvitationCodeForUpdateAction
{
    public function __construct(private InvitationCodeGenerator $codeGenerator) {}

    public function execute(string $code): BusinessInvitationCode
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Kode usaha harus diperiksa di dalam transaksi database.');
        }

        $invitation = BusinessInvitationCode::query()
            ->where('code_hash', $this->codeGenerator->hash($code))
            ->lockForUpdate()
            ->first();

        if (! $this->isUsable($invitation)) {
            throw ValidationException::withMessages([
                'business_code' => 'Kode Usaha tidak valid atau sudah tidak berlaku.',
            ]);
        }

        return $invitation;
    }

    private function isUsable(?BusinessInvitationCode $invitation): bool
    {
        if ($invitation === null || $invitation->revoked_at !== null) {
            return false;
        }

        if ($invitation->expires_at?->isPast()) {
            return false;
        }

        return $invitation->max_uses === null || $invitation->uses_count < $invitation->max_uses;
    }
}
