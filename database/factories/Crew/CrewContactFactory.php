<?php

declare(strict_types=1);

namespace Database\Factories\Crew;

use App\Models\Crew\CrewContact;
use App\Models\Promotions\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CrewContact> */
class CrewContactFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['promotion_id' => Promotion::factory(), 'client_id' => fake()->uuid(), 'name' => fake()->name(),
            'phone' => null, 'email' => null, 'notes' => null, 'version' => 1, 'archived_at' => null];
    }
}
