<?php

declare(strict_types=1);

namespace Database\Factories\Storylines;

use App\Enums\Storylines\StorylineStatus;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Storyline> */
class StorylineFactory extends Factory
{
    public function definition(): array
    {
        return ['promotion_id' => Promotion::factory(), 'title' => fake()->sentence(3), 'premise' => fake()->paragraph(), 'payoff' => null, 'status' => StorylineStatus::Active, 'version' => 1, 'submission_id' => (string) Str::uuid()];
    }
}
