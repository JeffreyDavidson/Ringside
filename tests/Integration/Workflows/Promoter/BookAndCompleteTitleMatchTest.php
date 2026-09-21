<?php

declare(strict_types=1);

use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\RecordResultAction;
use App\Data\Matches\EventMatchData;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Enums\Titles\TitleType;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Collection;

test('a promoter can book and complete a title match', function (): void {
    // Arrange
    $event = Event::factory()->past()->withVenue()->create();
    $referee = Referee::factory()->bookable()->create();
    $champion = Wrestler::factory()->bookable()->create();
    $challenger = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();

    $matchData = new EventMatchData(
        matchType: MatchType::Singles,
        referees: Referee::query()->whereKey($referee)->get(),
        titles: Title::query()->whereKey($title)->get(),
        sides: collect([
            1 => ['wrestlers' => [$champion]],
            2 => ['wrestlers' => [$challenger]],
        ]),
        preview: 'Championship main event',
    );

    // Act
    $match = resolve(AddMatchForEventAction::class)->handle($event, $matchData);
    $winningSide = $match->sides()->where('position', 2)->firstOrFail();
    $completedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        new MatchResultData(MatchFinish::Pinfall, $winningSide, new Collection),
    );

    // Assert
    expect($completedMatch->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($completedMatch->winning_side_id)->toBe($winningSide->id)
        ->and($completedMatch->referees()->whereKey($referee)->exists())->toBeTrue()
        ->and($completedMatch->titles()->whereKey($title)->exists())->toBeTrue()
        ->and($completedMatch->competitors()->count())->toBe(2)
        ->and(TitleChampionship::query()
            ->whereBelongsTo($title, 'title')
            ->where('champion_id', $champion->id)
            ->where('lost_match_id', $completedMatch->id)
            ->exists())->toBeTrue()
        ->and(TitleChampionship::query()
            ->whereBelongsTo($title, 'title')
            ->where('champion_id', $challenger->id)
            ->where('won_match_id', $completedMatch->id)
            ->exists())->toBeTrue();
});
