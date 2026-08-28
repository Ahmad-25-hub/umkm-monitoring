<?php

namespace App\Support;

use App\Models\BusinessInvitationCode;
use Illuminate\Support\Str;
use RuntimeException;

class InvitationCodeGenerator
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const SEGMENT_LENGTH = 4;

    private const SEGMENT_COUNT = 3;

    public function generateUnique(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $characters = '';

            for ($index = 0; $index < self::SEGMENT_LENGTH * self::SEGMENT_COUNT; $index++) {
                $characters .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            $code = implode('-', str_split($characters, self::SEGMENT_LENGTH));

            if (BusinessInvitationCode::query()->where('code_hash', $this->hash($code))->doesntExist()) {
                return $code;
            }
        }

        throw new RuntimeException('Tidak dapat membuat kode undangan yang unik.');
    }

    public function hash(string $code): string
    {
        $normalizedCode = Str::of($code)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->toString();

        return hash('sha256', $normalizedCode);
    }
}
