<?php

declare(strict_types=1);

use App\Actions\Matches\AddMatchForEventAction;
use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

test('it rejects a match without competitors', function () {
    $event = Event::factory()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::factory()->bookable()->count(1)->create(),
        Title::query()->whereKey([])->get(),
        collect(),
        null,
    );

    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $matchData))
        ->toThrow(InvalidMatchConfigurationException::class, 'A match must have competitors assigned.');
});

test('it rejects a match without referees', function () {
    $event = Event::factory()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey([])->get(),
        Title::query()->whereKey([])->get(),
        collect([
            1 => ['wrestlers' => [$firstWrestler]],
            2 => ['wrestlers' => [$secondWrestler]],
        ]),
        null,
    );

    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $matchData))
        ->toThrow(InvalidMatchConfigurationException::class, 'A match must have at least one referee assigned.');
});

test('it creates a complete side-based match', function () {
    $event = Event::factory()->create();
    $referee = Referee::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($referee)->get(),
        Title::query()->whereKey($title)->get(),
        collect([
            1 => ['wrestlers' => [$firstWrestler]],
            2 => ['wrestlers' => [$secondWrestler]],
        ]),
        'Opening contest',
    );

    $match = resolve(AddMatchForEventAction::class)->handle($event, $matchData);

    expect($match->event->is($event))->toBeTrue()
        ->and($match->match_number)->toBe(1)
        ->and($match->match_type)->toBe(MatchType::Singles)
        ->and($match->preview)->toBe('Opening contest')
        ->and($match->referees->modelKeys())->toBe([$referee->id])
        ->and($match->titles->modelKeys())->toBe([$title->id])
        ->and($match->sides()->pluck('position')->all())->toBe([1, 2])
        ->and($match->competitors()->pluck('competitor_id')->all())->toEqualCanonicalizing([
            $firstWrestler->id,
            $secondWrestler->id,
        ]);
});

test('it does not reuse match numbers from soft-deleted matches', function () {
    $event = Event::factory()->create();
    EventMatch::factory()->for($event)->create(['match_number' => 4])->delete();
    $referee = Referee::factory()->bookable()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($referee)->get(),
        Title::query()->whereKey([])->get(),
        collect([
            1 => ['wrestlers' => [$firstWrestler]],
            2 => ['wrestlers' => [$secondWrestler]],
        ]),
        null,
    );

    $match = resolve(AddMatchForEventAction::class)->handle($event, $matchData);

    expect($match->match_number)->toBe(5);
});

test('it rolls back the match when a side contains no eligible competitors', function () {
    $event = Event::factory()->create();
    $referee = Referee::factory()->bookable()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($referee)->get(),
        Title::query()->whereKey([])->get(),
        collect([
            1 => ['wrestlers' => [$bookableWrestler]],
            2 => ['wrestlers' => [$unemployedWrestler]],
        ]),
        null,
    );

    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $matchData))
        ->toThrow(EntityNotAvailableException::class)
        ->and(EventMatch::query()->whereBelongsTo($event)->exists())->toBeFalse();
});

test('it creates a title match when the current champion is a competitor', function () {
    $event = Event::factory()->create();
    $referee = Referee::factory()->bookable()->create();
    $champion = Wrestler::factory()->bookable()->create();
    $challenger = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($referee)->get(),
        Title::query()->whereKey($title)->get(),
        collect([
            1 => ['wrestlers' => [$champion]],
            2 => ['wrestlers' => [$challenger]],
        ]),
        null,
    );

    $match = resolve(AddMatchForEventAction::class)->handle($event, $matchData);

    expect($match->titles()->whereKey($title)->exists())->toBeTrue();
});

test('it rolls back a title match when the current champion is absent', function () {
    $event = Event::factory()->create();
    $referee = Referee::factory()->bookable()->create();
    $champion = Wrestler::factory()->bookable()->create();
    $firstChallenger = Wrestler::factory()->bookable()->create();
    $secondChallenger = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();
    $matchData = new EventMatchData(
        MatchType::Singles,
        Referee::query()->whereKey($referee)->get(),
        Title::query()->whereKey($title)->get(),
        collect([
            1 => ['wrestlers' => [$firstChallenger]],
            2 => ['wrestlers' => [$secondChallenger]],
        ]),
        null,
    );

    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $matchData))
        ->toThrow(InvalidMatchConfigurationException::class)
        ->and(EventMatch::query()->whereBelongsTo($event)->exists())->toBeFalse();
});

test('it rejects booking a tag team member individually on the same card', function () {
    $event = Event::factory()->create(['date' => now()->addDays(3)]);
    $member = Wrestler::factory()->bookable()->create();
    $partner = Wrestler::factory()->bookable()->create();
    $tagTeam = TagTeam::factory()->bookable()->withCurrentWrestlers([$member, $partner])->create();
    $tagTeamMatch = new EventMatchData(
        MatchType::TripleThreat,
        Referee::factory()->bookable()->count(1)->create(),
        Title::query()->whereKey([])->get(),
        collect([
            1 => ['tag_teams' => [$tagTeam]],
            2 => ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
            3 => ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
        ]),
        null,
    );
    $singlesMatch = new EventMatchData(
        MatchType::Singles,
        Referee::factory()->bookable()->count(1)->create(),
        Title::query()->whereKey([])->get(),
        collect([1 => ['wrestlers' => [$member]], 2 => ['wrestlers' => [Wrestler::factory()->bookable()->create()]]]),
        null,
    );
    resolve(AddMatchForEventAction::class)->handle($event, $tagTeamMatch);

    expect(fn () => resolve(AddMatchForEventAction::class)->handle($event, $singlesMatch))
        ->toThrow(SchedulingConflictException::class, "Wrestler [{$member->name}] is already booked at this event time.")
        ->and(EventMatch::query()->count())->toBe(1);
});

describe('promotion ownership', function (): void {
    it('rejects booking :dataset of another promotion on the event', function (Closure $matchData, string $entityType): void {
        // Arrange
        [$home, $foreign] = Promotion::factory()->count(2)->create()->all();
        $event = Event::factory()->for($home, 'promotion')->create();
        $data = $matchData($home, $foreign);

        // Act
        $book = fn (): EventMatch => resolve(AddMatchForEventAction::class)->handle($event, $data);

        // Assert
        expect($book)->toThrow(
            InvalidMatchConfigurationException::class,
            "Selected {$entityType} must all belong to the event's promotion.",
        );
        expect(EventMatch::query()->whereBelongsTo($event)->exists())->toBeFalse();
    })->with('cross promotion match bookings');

    it('books participants that belong to the event promotion', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->create();
        $data = new EventMatchData(
            MatchType::Singles,
            Referee::factory()->bookable()->for($promotion, 'promotion')->count(1)->create(),
            Title::factory()->active()->singles()->for($promotion, 'promotion')->count(1)->create(),
            collect([
                1 => ['wrestlers' => [Wrestler::factory()->bookable()->for($promotion, 'promotion')->create()]],
                2 => ['wrestlers' => [Wrestler::factory()->bookable()->for($promotion, 'promotion')->create()]],
            ]),
            null,
        );

        // Act
        $match = resolve(AddMatchForEventAction::class)->handle($event, $data);

        // Assert
        expect($match->event_id)->toBe($event->id)
            ->and($match->wrestlers()->count())->toBe(2)
            ->and($match->titles()->count())->toBe(1);
    });
});
