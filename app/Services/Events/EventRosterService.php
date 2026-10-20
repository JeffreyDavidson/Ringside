<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\EventRosterQuery;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Collection;

/** Everyone on an event's card, as booked (wrestlers, tag teams, referees) and as individuals (tag teams replaced by their current members). */
class EventRosterService
{
    public function __construct(private readonly EventRosterQuery $matches) {}

    /**
     * @return array{entries: Collection<int, Wrestler|Referee|TagTeam>, people: Collection<int, Wrestler|Referee>}
     */
    public function forEvent(Event $event, bool $excludePastResults = false): array
    {
        return $this->fromMatches($this->matches->get($event, $excludePastResults), $event);
    }

    /**
     * The people on matches that were read with their competitors, referees and current tag team members. A deleted
     * competitor is still on the roster; whether deleted referees and members are depends on how the matches were read.
     *
     * @param  ModelCollection<int, EventMatch>  $matches
     * @return array{entries: Collection<int, Wrestler|Referee|TagTeam>, people: Collection<int, Wrestler|Referee>}
     */
    public function fromMatches(ModelCollection $matches, Event $event): array
    {
        $entries = $matches->toBase()->flatMap(fn (EventMatch $match): Collection => $match->competitors->competitorModels()
            ->merge($match->referees->toBase()))
            ->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person->promotion_id === $event->promotion_id)
            ->unique(self::key(...))->values();
        $members = $entries->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof TagTeam)
            ->flatMap(fn (TagTeam $team): Collection => $team->currentWrestlers->toBase());

        return [
            'entries' => $entries,
            'people' => $entries->reject(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof TagTeam)
                ->merge($members)->unique(self::key(...))->values(),
        ];
    }

    /** The `type:id` key that tells two people apart across wrestlers, tag teams and referees. */
    public static function key(Wrestler|Referee|TagTeam $person): string
    {
        return $person->getMorphClass().':'.$person->id;
    }
}
