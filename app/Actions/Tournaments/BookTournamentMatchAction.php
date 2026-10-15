<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Actions\Matches\AddMatchForEventAction;
use App\Data\Matches\EventMatchData;
use App\Enums\Matches\MatchType;
use App\Enums\Tournaments\EntrantKind;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentNode;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Database\Eloquent\Collection;

class BookTournamentMatchAction
{
    public function __construct(private readonly AddMatchForEventAction $bookMatch, private readonly TournamentBracketService $bracket) {}

    /**
     * @param  Collection<int, Referee>  $referees
     * @param  Collection<int, Title>|null  $titles
     */
    public function handle(TournamentNode $node, Event $event, Collection $referees, int $version, ?Collection $titles = null, ?MatchStipulation $stipulation = null): EventMatch
    {
        $bout = $this->bracket->bout($node);
        if (! $bout->inputsResolved || ! $bout->first instanceof TournamentEntry || ! $bout->second instanceof TournamentEntry) {
            throw TournamentException::because('unresolved');
        }
        $kind = $node->tournament()->firstOrFail()->entrant_kind;
        $first = $bout->first->competitor;
        $second = $bout->second->competitor;
        if ($kind === EntrantKind::Wrestler) {
            if (! $first instanceof Wrestler || ! $second instanceof Wrestler) {
                throw TournamentException::because('entrants_changed');
            }

            return $this->bookMatch->handle($event, new EventMatchData(MatchType::Singles, $referees, $titles ?? new Collection, collect([1 => ['wrestlers' => [$first]], 2 => ['wrestlers' => [$second]]]), null, $stipulation), $node, $version);
        }
        if (! $first instanceof TagTeam || ! $second instanceof TagTeam) {
            throw TournamentException::because('entrants_changed');
        }

        return $this->bookMatch->handle($event, new EventMatchData(MatchType::TagTeam, $referees, $titles ?? new Collection, collect([1 => ['tag_teams' => [$first]], 2 => ['tag_teams' => [$second]]]), null, $stipulation), $node, $version);

    }
}
