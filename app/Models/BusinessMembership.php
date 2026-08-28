<?php

namespace App\Models;

use Database\Factories\BusinessMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['business_id', 'user_id', 'role', 'status'])]
class BusinessMembership extends Model
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_EMPLOYEE = 'employee';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** @use HasFactory<BusinessMembershipFactory> */
    use HasFactory;

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActiveOwner(): bool
    {
        return $this->role === self::ROLE_OWNER && $this->status === self::STATUS_ACTIVE;
    }
}
