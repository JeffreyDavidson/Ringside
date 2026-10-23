<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Actions\Matches\AddMatchForEventAction;
use App\Data\Matches\EventMatchData;
use App\Data\Tournaments\TournamentBookingData;
use App\Enums\Matches\MatchType;
use App\Enums\Tournaments\EntrantKind;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentNode;
use App\Models\Users\User;
use App\Services\Tournaments\TournamentBracketService;
use App\ValueObjects\MatchSides;

class BookTournamentMatchAction
{
    public function __construct(private readonly AddMatchForEventAction $bookMatch, private readonly TournamentBracketService $bracket) {}

    public function handle(TournamentNode $node, int $version, TournamentBookingData $data, User $actor): EventMatch
    {
        $bout = $this->bracket->bout($node);

        if (! $bout->inputsResolved || ! $bout->first instanceof TournamentEntry || ! $bout->second instanceof TournamentEntry) {
            throw TournamentException::unresolved();
        }

        $kind = $node->tournament()->firstOrFail()->entrant_kind;
        $first = $bout->first->competitor;
        $second = $bout->second->competitor;

        if ($kind === EntrantKind::Wrestler) {
            if (! $first instanceof Wrestler || ! $second instanceof Wrestler) {
                throw TournamentException::entrantsChanged();
            }

            return $this->bookMatch->handle($data->event, new EventMatchData(MatchType::Singles, $data->referees, $data->titles, new MatchSides(collect([1 => ['wrestlers' => [$first]], 2 => ['wrestlers' => [$second]]])), null, $data->stipulation), $actor, $node, $version);
        }

        if (! $first instanceof TagTeam || ! $second instanceof TagTeam) {
            throw TournamentException::entrantsChanged();
        }

        return $this->bookMatch->handle($data->event, new EventMatchData(MatchType::TagTeam, $data->referees, $data->titles, new MatchSides(collect([1 => ['tag_teams' => [$first]], 2 => ['tag_teams' => [$second]]])), null, $data->stipulation), $actor, $node, $version);

    }
}
