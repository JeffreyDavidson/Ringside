<?php

declare(strict_types=1);

namespace Database\Factories\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionInvitation>
 */
class PromotionInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => MembershipRole::Member,
        ];
    }

    public function forEmail(string $email): static
    {
        return $this->state(['email' => $email]);
    }

    public function withRole(MembershipRole $role): static
    {
        return $this->state(['role' => $role]);
    }
}
