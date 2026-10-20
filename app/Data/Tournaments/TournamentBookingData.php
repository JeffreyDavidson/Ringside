<?php

declare(strict_types=1);

namespace App\Data\Tournaments;

use App\Models\Events\Event;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Collection;

readonly class TournamentBookingData
{
    /**
     * Create a new tournament booking data instance.
     *
     * @param  Collection<int, Referee>  $referees
     * @param  Collection<int, Title>  $titles
     */
    public function __construct(
        public Event $event,
        public Collection $referees,
        public Collection $titles = new Collection,
        public ?MatchStipulation $stipulation = null,
    ) {}
}
