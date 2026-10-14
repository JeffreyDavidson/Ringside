<?php

declare(strict_types=1);

namespace Database\Factories\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineStep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<StorylineStep> */
class StorylineStepFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['storyline_id' => Storyline::factory(), 'promotion_id' => fn (array $attributes): int => Storyline::query()->findOrFail($attributes['storyline_id'])->promotion_id, 'heading' => 'Planned confrontation', 'description' => 'The partners confront one another.', 'sequence' => 1, 'event_id' => null, 'match_id' => null, 'segment_id' => null, 'target_on' => null, 'target_at' => null, 'timezone' => 'UTC', 'source_context' => [], 'submission_id' => (string) Str::uuid(), 'status' => StepStatus::Planned, 'version' => 1];
    }
}
