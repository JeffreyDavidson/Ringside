<?php

declare(strict_types=1);

namespace Database\Factories\Lifecycle;

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Stables\Stable;
use App\Models\Titles\Title;
use Database\Factories\Concerns\HasLifecyclePeriodStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<ActivityPeriod> */
class ActivityPeriodFactory extends Factory
{
    use HasLifecyclePeriodStates;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'started_at' => Carbon::yesterday(),
            'ended_at' => null,
        ];
    }

    public function forRandomActiveable(): static
    {
        return $this->for(fake()->randomElement([
            Stable::factory(),
            Title::factory(),
        ]), 'activeable');
    }
}
