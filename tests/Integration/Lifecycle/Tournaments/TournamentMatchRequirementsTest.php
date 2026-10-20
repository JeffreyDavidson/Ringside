<?php

declare(strict_types=1);

use App\Data\Matches\EventMatchData;
use App\Data\Tournaments\TournamentBoutData;
use App\Enums\Matches\MatchType;
use App\Enums\Tournaments\EntrantKind;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Tournaments\TournamentMatchRequirements;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentNode;
use App\Services\Tournaments\TournamentBracketService;
use App\ValueObjects\MatchSides;
use Illuminate\Database\Eloquent\Collection;

/**
 * The first-round bout of a tag team tournament and the match data that books its two teams as the given type.
 *
 * @return array{world: array{tournament: Tournament, promotion: Promotion, event: Event, referees: Collection<int, Referee>}, node: TournamentNode, bout: TournamentBoutData, data: EventMatchData}
 */
function tagTeamBoutData(MatchType $type): array
{
    $world = tournamentFixture(kind: EntrantKind::TagTeam);
    $node = $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail();
    $entries = $world['tournament']->entries()->get();
    $first = TagTeam::query()->findOrFail($entries->sole('seed', 1)->competitor_id);
    $second = TagTeam::query()->findOrFail($entries->sole('seed', 4)->competitor_id);

    return [
        'world' => $world,
        'node' => $node,
        'bout' => resolve(TournamentBracketService::class)->bout($node),
        'data' => new EventMatchData($type, $world['referees'], new Collection, new MatchSides(collect([1 => ['tag_teams' => [$first]], 2 => ['tag_teams' => [$second]]])), null),
    ];
}

describe('tournament match requirements', function (): void {
    it('lets a tag team tournament bout be booked as a tag team match', function (): void {
        // Arrange
        ['world' => $world, 'node' => $node, 'bout' => $bout, 'data' => $data] = tagTeamBoutData(MatchType::TagTeam);
        $requirements = resolve(TournamentMatchRequirements::class);

        // Act
        $check = fn () => $requirements->ensureBookable($world['tournament'], $node, $bout, $world['event'], $data);

        // Assert
        expect($check)->not->toThrow(TournamentException::class);
    });

    it('refuses to book a tag team tournament bout as a singles match', function (): void {
        // Arrange
        ['world' => $world, 'node' => $node, 'bout' => $bout, 'data' => $data] = tagTeamBoutData(MatchType::Singles);
        $requirements = resolve(TournamentMatchRequirements::class);

        // Act
        $check = fn () => $requirements->ensureBookable($world['tournament'], $node, $bout, $world['event'], $data);

        // Assert
        expect($check)->toThrow(TournamentException::class, __('tournaments.errors.entrants_changed'));
    });
});
