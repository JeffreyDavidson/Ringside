<?php

declare(strict_types=1);

namespace Database\Factories\Storylines;

use App\Enums\Storylines\StorylineRevisionRecord;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineRevision;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StorylineRevision> */
class StorylineRevisionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['storyline_id' => Storyline::factory(), 'promotion_id' => fn (array $attributes): int => Storyline::query()->findOrFail($attributes['storyline_id'])->promotion_id, 'actor_id' => User::factory(), 'operation' => 'story_revised', 'record_type' => StorylineRevisionRecord::Storyline, 'record_id' => 1, 'reason' => 'A revised direction.', 'before' => [], 'after' => []];
    }
}
