<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessMembership>
 */
class BusinessMembershipFactory extends Factory
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
            'user_id' => User::factory(),
            'role' => BusinessMembership::ROLE_OWNER,
            'status' => BusinessMembership::STATUS_ACTIVE,
        ];
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => BusinessMembership::ROLE_EMPLOYEE,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BusinessMembership::STATUS_INACTIVE,
        ]);
    }
}
