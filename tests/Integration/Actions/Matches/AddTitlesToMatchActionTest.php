<?php

declare(strict_types=1);

use App\Actions\Matches\AddTitlesToMatchAction;
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

    AddTitlesToMatchAction::run($match, $titles);

    // Should create title-match relationship
    $this->assertDatabaseHas('events_matches_titles', [
        'match_id' => $match->id,
        'title_id' => $title->id,
    ]);

    // Match should have the title
    expect($match->fresh()->titles)->toHaveCount(1);
    expect($match->fresh()->titles->first()->id)->toBe($title->id);
});

test('it adds multiple titles to a match', function () {
    $match = EventMatch::factory()->create();
    $title1 = Title::factory()->active()->create(['name' => 'WWE Championship']);
    $title2 = Title::factory()->active()->create(['name' => 'Universal Championship']);

    $titles = collect([$title1, $title2]);

    AddTitlesToMatchAction::run($match, $titles);

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
    expect($match->fresh()->titles)->toHaveCount(2);
    expect($match->fresh()->titles->pluck('id'))->toContain($title1->id, $title2->id);
});

test('it filters out inactive titles', function () {
    $match = EventMatch::factory()->create();
    $activeTitle = Title::factory()->active()->create();
    $inactiveTitle = Title::factory()->inactive()->create();

    $titles = collect([$activeTitle, $inactiveTitle]);

    AddTitlesToMatchAction::run($match, $titles);

    // Should only add the active title
    $this->assertDatabaseHas('events_matches_titles', [
        'match_id' => $match->id,
        'title_id' => $activeTitle->id,
    ]);

    $this->assertDatabaseMissing('events_matches_titles', [
        'match_id' => $match->id,
        'title_id' => $inactiveTitle->id,
    ]);

    expect($match->fresh()->titles)->toHaveCount(1);
    expect($match->fresh()->titles->first()->id)->toBe($activeTitle->id);
});

test('it throws exception when no eligible titles provided', function () {
    $match = EventMatch::factory()->create();
    $inactiveTitle = Title::factory()->inactive()->create();

    $titles = collect([$inactiveTitle]);

    expect(fn () => AddTitlesToMatchAction::run($match, $titles))
        ->toThrow(InvalidArgumentException::class, 'No eligible titles provided for championship match');
});

test('it handles empty collection', function () {
    $match = EventMatch::factory()->create();
    $titles = collect([]);

    expect(fn () => AddTitlesToMatchAction::run($match, $titles))
        ->toThrow(InvalidArgumentException::class, 'No eligible titles provided for championship match');
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

    AddTitlesToMatchAction::run($match, $titles);

    // Match should be associated with both titles
    $matchTitles = $match->fresh()->titles;
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

    AddTitlesToMatchAction::run($match, $titles);

    // Both titles should be added atomically
    expect($match->fresh()->titles)->toHaveCount(2);

    // Verify both database records exist
    $this->assertDatabaseCount('events_matches_titles', 2);
});
