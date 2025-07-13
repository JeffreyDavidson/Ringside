<?php

declare(strict_types=1);

namespace App\Data\Matches;

use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

readonly class EventMatchData
{
    /**
     * Create a new event match data instance.
     *
     * @param  EloquentCollection<int, Referee>  $referees
     * @param  EloquentCollection<int, Title>  $titles
     * @param  Collection<"wrestlers"|"tag_teams", array<int, Wrestler|TagTeam>>  $competitors
     */
    public function __construct(
        public MatchType $matchType,
        public EloquentCollection $referees,
        public EloquentCollection $titles,
        public Collection $competitors,
        public ?string $preview
    ) {}
}
