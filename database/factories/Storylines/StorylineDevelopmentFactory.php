<?php

declare(strict_types=1);

namespace Database\Factories\Storylines;

use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<StorylineDevelopment> */
class StorylineDevelopmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['storyline_id' => Storyline::factory(), 'promotion_id' => fn (array $attributes): int => Storyline::query()->findOrFail($attributes['storyline_id'])->promotion_id, 'heading' => 'Actual confrontation', 'description' => 'The partners argued.', 'occurred_on' => today(), 'sequence' => 1, 'event_id' => null, 'match_id' => null, 'segment_id' => null, 'timezone' => 'UTC', 'source_context' => [], 'cast' => [], 'submission_id' => (string) Str::uuid(), 'version' => 1];
    }
}
