<?php

declare(strict_types=1);

use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\RecordResultAction;
use App\Data\Matches\EventMatchData;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\Title;
use Illuminate\Support\Collection;

test('a promoter can book and complete a tag team match', function (): void {
    // Arrange
    $event = Event::factory()->past()->withVenue()->create();
    $referee = Referee::factory()->bookable()->create();
    $firstTagTeam = TagTeam::factory()->bookable()->create();
    $secondTagTeam = TagTeam::factory()->bookable()->create();

    $matchData = new EventMatchData(
        matchType: MatchType::TagTeam,
        referees: Referee::query()->whereKey($referee)->get(),
        titles: Title::query()->whereKey([])->get(),
        sides: collect([
            1 => ['tag_teams' => [$firstTagTeam]],
            2 => ['tag_teams' => [$secondTagTeam]],
        ]),
        preview: 'Tag team showcase',
    );

    // Act
    $match = resolve(AddMatchForEventAction::class)->handle($event, $matchData);
    $winningSide = $match->sides()->where('position', 1)->firstOrFail();
    $completedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        new MatchResultData(MatchFinish::Pinfall, $winningSide, new Collection),
    );

    // Assert
    expect($completedMatch->refresh()->match_type)->toBe(MatchType::TagTeam)
        ->and($completedMatch->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($completedMatch->winning_side_id)->toBe($winningSide->id)
        ->and($completedMatch->referees()->whereKey($referee)->exists())->toBeTrue()
        ->and($completedMatch->competitors()->count())->toBe(2)
        ->and($completedMatch->competitors()->where('competitor_id', $firstTagTeam->id)->exists())->toBeTrue()
        ->and($completedMatch->competitors()->where('competitor_id', $secondTagTeam->id)->exists())->toBeTrue();
});
