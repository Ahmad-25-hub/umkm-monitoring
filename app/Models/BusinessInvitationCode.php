<?php

namespace App\Models;

use Database\Factories\BusinessInvitationCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'created_by_user_id',
    'code_hash',
    'expires_at',
    'max_uses',
    'uses_count',
    'revoked_at',
])]
#[Hidden(['code_hash'])]
class BusinessInvitationCode extends Model
{
    /** @use HasFactory<BusinessInvitationCodeFactory> */
    use HasFactory;

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
