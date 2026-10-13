<?php

declare(strict_types=1);

namespace Database\Factories\Sponsors;

use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Sponsor> */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['promotion_id' => Promotion::factory(), 'name' => fake()->company(), 'submission_key' => (string) Str::uuid(), 'version' => 1];
    }
}
