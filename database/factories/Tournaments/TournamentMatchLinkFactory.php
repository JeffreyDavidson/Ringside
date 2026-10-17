<?php

declare(strict_types=1);

namespace Database\Factories\Tournaments;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TournamentMatchLink> */
class TournamentMatchLinkFactory extends Factory
{
    /** @return array<string,mixed> */
    public function definition(): array
    {
        return ['node_id' => TournamentNode::factory(), 'tournament_id' => fn (array $attributes): int => TournamentNode::query()->findOrFail($attributes['node_id'])->tournament_id, 'promotion_id' => fn (array $attributes): int => TournamentNode::query()->findOrFail($attributes['node_id'])->promotion_id, 'active_node_id' => fn (array $attributes): int => $attributes['node_id'], 'match_id' => fn (array $attributes): int => EventMatch::factory()->for(Event::factory()->state(['promotion_id' => $attributes['promotion_id']]))->create()->id, 'attempt' => 1];
    }
}
