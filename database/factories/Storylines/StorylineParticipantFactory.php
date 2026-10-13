<?php

declare(strict_types=1);

namespace Database\Factories\Storylines;

use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StorylineParticipant> */
class StorylineParticipantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['storyline_id' => Storyline::factory(), 'promotion_id' => fn (array $attributes): int => Storyline::query()->findOrFail($attributes['storyline_id'])->promotion_id, 'participant_type' => 'wrestler', 'participant_id' => fn (array $attributes): int => Wrestler::factory()->for(Promotion::query()->findOrFail(Storyline::query()->findOrFail($attributes['storyline_id'])->promotion_id))->create()->id, 'label' => 'Creative participant', 'role' => null, 'joined_on' => today(), 'left_on' => null];
    }
}
