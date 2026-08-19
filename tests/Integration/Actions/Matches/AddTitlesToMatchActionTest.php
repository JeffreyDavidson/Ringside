<?php

declare(strict_types=1);

use App\Actions\Matches\AddTitlesToMatchAction;
use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

function createSinglesMatchWithCompetitors(): EventMatch
{
    return EventMatch::factory()
        ->withCompetitors(Wrestler::factory()->bookable()->count(2)->create()->all())
        ->create();
}

test('it adds a single title to a match', function () {
    $match = createSinglesMatchWithCompetitors();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);

    $titles = collect([$title]);

    resolve(AddTitlesToMatchAction::class)->handle($match, $titles);

    // Should create title-match relationship
    $this->assertDatabaseHas('events_matches_titles', [
        'match_id' => $match->id,
        'title_id' => $title->id,
    ]);

    // Match should have the title
    expect($match->refresh()->titles)->toHaveCount(1);
    expect($match->refresh()->titles->firstOrFail()->id)->toBe($title->id);
});

test('it adds multiple titles to a match', function () {
    $match = createSinglesMatchWithCompetitors();
    $title1 = Title::factory()->active()->create([
        'name' => 'WWE Championship',
        'type' => TitleType::Singles,
    ]);
    $title2 = Title::factory()->active()->create([
        'name' => 'Universal Championship',
        'type' => TitleType::Singles,
    ]);

    $titles = collect([$title1, $title2]);

    resolve(AddTitlesToMatchAction::class)->handle($match, $titles);

    // Should create relationships for both titles
    $this->assertDatabaseHas('events_matches_titles', [
        'match_id' => $match->id,
        'title_id' => $title1->id,
    ]);

    $this->assertDatabaseHas('events_matches_titles', [
        'match_id' => $match->id,
        'title_id' => $title2->id,
    ]);

    // Match should have both titles
    expect($match->refresh()->titles)->toHaveCount(2);
    expect($match->refresh()->titles->pluck('id'))->toContain($title1->id, $title2->id);
});

test('it rejects the entire assignment when any title is inactive', function () {
    $match = EventMatch::factory()->create();
    $activeTitle = Title::factory()->active()->create();
    $inactiveTitle = Title::factory()->inactive()->create();

    $titles = collect([$activeTitle, $inactiveTitle]);

    expect(fn () => resolve(AddTitlesToMatchAction::class)->handle($match, $titles))
        ->toThrow(EntityNotAvailableException::class, 'Selected titles must all be eligible for match assignment.');

    expect($match->titles()->count())->toBe(0);
});

test('it throws exception when no eligible titles provided', function () {
    $match = EventMatch::factory()->create();
    $inactiveTitle = Title::factory()->inactive()->create();

    $titles = collect([$inactiveTitle]);

    expect(fn () => resolve(AddTitlesToMatchAction::class)->handle($match, $titles))
        ->toThrow(EntityNotAvailableException::class, 'Selected titles must all be eligible for match assignment.');
});

test('it handles empty collection', function () {
    $match = EventMatch::factory()->create();
    $titles = Title::query()->whereKey([])->get();

    expect(fn () => resolve(AddTitlesToMatchAction::class)->handle($match, $titles))
        ->toThrow(EntityNotAvailableException::class, 'Selected titles must all be eligible for match assignment.');
});

test('it creates championship match correctly', function () {
    $match = createSinglesMatchWithCompetitors();
    $wweChampionship = Title::factory()->active()->create([
        'name' => 'WWE Championship',
        'type' => TitleType::Singles,
    ]);
    $intercontinentalTitle = Title::factory()->active()->create([
        'name' => 'Intercontinental Championship',
        'type' => TitleType::Singles,
    ]);

    $titles = collect([$wweChampionship, $intercontinentalTitle]);

    resolve(AddTitlesToMatchAction::class)->handle($match, $titles);

    // Match should be associated with both titles
    $matchTitles = $match->refresh()->titles;
    expect($matchTitles)->toHaveCount(2);

    $titleNames = $matchTitles->pluck('name')->toArray();
    expect($titleNames)->toContain('WWE Championship');
    expect($titleNames)->toContain('Intercontinental Championship');
});

test('it handles transaction consistency', function () {
    $match = createSinglesMatchWithCompetitors();
    $title1 = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $title2 = Title::factory()->active()->create(['type' => TitleType::Singles]);

    $titles = collect([$title1, $title2]);

    resolve(AddTitlesToMatchAction::class)->handle($match, $titles);

    // Both titles should be added atomically
    expect($match->refresh()->titles)->toHaveCount(2);

    // Verify both database records exist
    $this->assertDatabaseCount('events_matches_titles', 2);
});

test('it rejects a title already assigned on the event card', function () {
    $event = Event::factory()->scheduled()->create();
    $existingMatch = EventMatch::factory()->forEvent($event)->create();
    $targetMatch = EventMatch::factory()->forEvent($event)->create();
    $title = Title::factory()->active()->create();

    $existingMatch->titles()->attach($title);

    expect(fn () => resolve(AddTitlesToMatchAction::class)->handle($targetMatch, collect([$title])))
        ->toThrow(SchedulingConflictException::class, "Title [{$title->name}] is already assigned at this event time.");

    expect($targetMatch->titles()->count())->toBe(0);
});

test('it assigns a repeated title only once', function () {
    $match = createSinglesMatchWithCompetitors();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);

    resolve(AddTitlesToMatchAction::class)->handle($match, collect([$title, $title]));

    expect($match->titles()->count())->toBe(1);
});

test('it requires the current wrestler champion to compete in the title match', function () {
    $match = EventMatch::factory()->create();
    $side = MatchSide::factory()->for($match, 'match')->create();
    $champion = Wrestler::factory()->bookable()->create();
    $challenger = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->create([
        'competitor_type' => $challenger->getMorphClass(),
        'competitor_id' => $challenger->id,
    ]);

    expect(fn () => resolve(AddTitlesToMatchAction::class)->handle($match, collect([$title])))
        ->toThrow(
            InvalidMatchConfigurationException::class,
            "The current champion of [{$title->name}] must compete in the title match.",
        );

    expect($match->titles()->exists())->toBeFalse();
});

test('it accepts a title match containing the current wrestler champion', function () {
    $match = EventMatch::factory()->create();
    $side = MatchSide::factory()->for($match, 'match')->create();
    $champion = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->create([
        'competitor_type' => $champion->getMorphClass(),
        'competitor_id' => $champion->id,
    ]);

    resolve(AddTitlesToMatchAction::class)->handle($match, collect([$title]));

    expect($match->titles()->whereKey($title)->exists())->toBeTrue();
});

test('it accepts a title match containing the current tag team champion', function () {
    $match = EventMatch::factory()->create();
    $side = MatchSide::factory()->for($match, 'match')->create();
    $champion = TagTeam::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::TagTeam]);
    TitleChampionship::factory()->for($title)->forTagTeam($champion)->current()->create();
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->create([
        'competitor_type' => $champion->getMorphClass(),
        'competitor_id' => $champion->id,
    ]);

    resolve(AddTitlesToMatchAction::class)->handle($match, collect([$title]));

    expect($match->titles()->whereKey($title)->exists())->toBeTrue();
});

test('it rejects a singles title assigned to tag team competitors', function () {
    $match = EventMatch::factory()->create();
    $side = MatchSide::factory()->for($match, 'match')->create();
    $tagTeam = TagTeam::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->create([
        'competitor_type' => $tagTeam->getMorphClass(),
        'competitor_id' => $tagTeam->id,
    ]);

    expect(fn () => resolve(AddTitlesToMatchAction::class)->handle($match, collect([$title])))
        ->toThrow(
            InvalidMatchConfigurationException::class,
            "The [{$title->name}] cannot be contested by this match's competitor type.",
        );

    expect($match->titles()->exists())->toBeFalse();
});

test('it rejects a tag team title assigned to wrestler competitors', function () {
    $match = EventMatch::factory()->create();
    $side = MatchSide::factory()->for($match, 'match')->create();
    $wrestler = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::TagTeam]);
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($side, 'side')->create([
        'competitor_type' => $wrestler->getMorphClass(),
        'competitor_id' => $wrestler->id,
    ]);

    expect(fn () => resolve(AddTitlesToMatchAction::class)->handle($match, collect([$title])))
        ->toThrow(
            InvalidMatchConfigurationException::class,
            "The [{$title->name}] cannot be contested by this match's competitor type.",
        );

    expect($match->titles()->exists())->toBeFalse();
});
