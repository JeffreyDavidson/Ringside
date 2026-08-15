<?php

declare(strict_types=1);

use App\Actions\Matches\AddTitlesToMatchAction;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it adds a single title to a match', function () {
    $match = EventMatch::factory()->create();
    $title = Title::factory()->active()->create();

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
    $match = EventMatch::factory()->create();
    $title1 = Title::factory()->active()->create(['name' => 'WWE Championship']);
    $title2 = Title::factory()->active()->create(['name' => 'Universal Championship']);

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
    $match = EventMatch::factory()->create();
    $wweChampionship = Title::factory()->active()->create([
        'name' => 'WWE Championship',
    ]);
    $intercontinentalTitle = Title::factory()->active()->create([
        'name' => 'Intercontinental Championship',
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
    $match = EventMatch::factory()->create();
    $title1 = Title::factory()->active()->create();
    $title2 = Title::factory()->active()->create();

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
    $match = EventMatch::factory()->create();
    $title = Title::factory()->active()->create();

    resolve(AddTitlesToMatchAction::class)->handle($match, collect([$title, $title]));

    expect($match->titles()->count())->toBe(1);
});
