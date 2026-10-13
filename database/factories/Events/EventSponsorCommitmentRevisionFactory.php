<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Models\Events\EventSponsorCommitment;
use App\Models\Events\EventSponsorCommitmentRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventSponsorCommitmentRevision> */
class EventSponsorCommitmentRevisionFactory extends Factory
{
    protected $model = EventSponsorCommitmentRevision::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['commitment_id' => EventSponsorCommitment::factory(),
            'promotion_id' => fn (array $attributes): int => EventSponsorCommitment::query()->findOrFail($attributes['commitment_id'])->promotion_id,
            'version' => 1, 'actor_name' => 'Show operator', 'operation' => 'save', 'snapshot' => []];
    }
}
