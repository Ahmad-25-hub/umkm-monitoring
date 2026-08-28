<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessInvitationCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessInvitationCode>
 */
class BusinessInvitationCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'created_by_user_id' => User::factory(),
            'code_hash' => hash('sha256', fake()->unique()->uuid()),
            'expires_at' => null,
            'max_uses' => null,
            'uses_count' => 0,
            'revoked_at' => null,
        ];
    }
}
