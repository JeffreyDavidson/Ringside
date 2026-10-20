<?php

declare(strict_types=1);

namespace App\Data\Matches;

use App\Enums\Matches\MatchType;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;
use App\ValueObjects\MatchSides;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

readonly class EventMatchData
{
    /**
     * Create a new event match data instance.
     *
     * @param  EloquentCollection<int, Referee>  $referees
     * @param  EloquentCollection<int, Title>  $titles
     */
    public function __construct(
        public MatchType $matchType,
        public EloquentCollection $referees,
        public EloquentCollection $titles,
        public MatchSides $sides,
        public ?string $preview,
        public ?MatchStipulation $matchStipulation = null,
        public ?int $tournamentVersion = null,
    ) {}
}
