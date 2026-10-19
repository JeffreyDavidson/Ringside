<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Rules\Titles\CurrentChampionIsCompeting;
use Illuminate\Support\Facades\Validator;

test('it rejects a title when its current wrestler champion is not competing', function () {
    $title = Title::factory()->active()->create();
    $champion = Wrestler::factory()->create();
    $challenger = Wrestler::factory()->create();
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();

    $validator = Validator::make([
        'competitors' => [
            ['wrestlers' => [$challenger->id]],
        ],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new CurrentChampionIsCompeting],
    ]);

    expect($validator->errors()->has('titles.0'))->toBeTrue();
});

test('it accepts a title when its current wrestler champion is competing', function () {
    $title = Title::factory()->active()->create();
    $champion = Wrestler::factory()->create();
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();

    $validator = Validator::make([
        'competitors' => [
            ['wrestlers' => [$champion->id]],
        ],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new CurrentChampionIsCompeting],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('it accepts a title when its current tag team champion is competing', function () {
    $title = Title::factory()->active()->create();
    $champion = TagTeam::factory()->create();
    TitleChampionship::factory()->for($title)->forTagTeam($champion)->current()->create();

    $validator = Validator::make([
        'competitors' => [
            ['tag_teams' => [(string) $champion->id]],
        ],
        'titles' => [(string) $title->id],
    ], [
        'titles.*' => [new CurrentChampionIsCompeting],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('it accepts a vacant title', function () {
    $title = Title::factory()->active()->create();

    $validator = Validator::make([
        'competitors' => [],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new CurrentChampionIsCompeting],
    ]);

    expect($validator->passes())->toBeTrue();
});

it('ignores a title identifier that is not numeric', function (mixed $value) {
    $validator = Validator::make([
        'competitors' => [['wrestlers' => [1]]],
        'titles' => [$value],
    ], [
        'titles.*' => [new CurrentChampionIsCompeting],
    ]);

    $passes = $validator->passes();

    expect($passes)->toBeTrue();
})->with([
    'text' => ['champion'],
    'array' => [[1]],
    'boolean' => [true],
]);

it('rejects a title with a current champion when the competitors are malformed', function (mixed $competitors) {
    $title = Title::factory()->active()->create();
    $champion = Wrestler::factory()->create();
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();

    $validator = Validator::make([
        'competitors' => $competitors,
        'titles' => [$title->id],
    ], [
        'titles.*' => [new CurrentChampionIsCompeting],
    ]);

    $message = $validator->errors()->first('titles.0');

    expect($message)->toBe('The current champion must be included in title matches.');
})->with([
    'competitors is not a list' => 'wrestlers',
    'a side is not an array' => [['not-a-side']],
    'wrestler identifiers are not a list' => [[['wrestlers' => 'wrestler']]],
    'identifiers are not scalar' => [[['wrestlers' => [[1], null]]]],
]);

it('finds the champion on a later side after skipping malformed sides', function () {
    $title = Title::factory()->active()->create();
    $champion = Wrestler::factory()->create();
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();

    $validator = Validator::make([
        'competitors' => [
            'not-a-side',
            ['wrestlers' => 'wrestler'],
            ['wrestlers' => [$champion->id]],
        ],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new CurrentChampionIsCompeting],
    ]);

    $passes = $validator->passes();

    expect($passes)->toBeTrue();
});

test('it requires the champion on the show date to compete, not the current champion', function () {
    // Arrange
    $title = Title::factory()->active()->create();
    $championThen = Wrestler::factory()->create();
    $championNow = Wrestler::factory()->create();
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler($championThen)
        ->wonOn('2026-02-01 00:00:00')
        ->lostOn('2026-05-01 00:00:00')
        ->create();
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler($championNow)
        ->wonOn('2026-05-01 00:00:00')
        ->current()
        ->create();
    $event = Event::factory()->create(['date' => '2026-03-10 19:00:00']);

    // Act
    $currentChampionOnly = Validator::make([
        'competitors' => [['wrestlers' => [$championNow->id]]],
        'titles' => [$title->id],
    ], ['titles.*' => [new CurrentChampionIsCompeting($event)]]);
    $championOnShowDate = Validator::make([
        'competitors' => [['wrestlers' => [$championThen->id]]],
        'titles' => [$title->id],
    ], ['titles.*' => [new CurrentChampionIsCompeting($event)]]);

    // Assert
    expect($currentChampionOnly->errors()->get('titles.0'))->toBe([__('titles.validation.champion_must_compete')])
        ->and($championOnShowDate->passes())->toBeTrue();
});

test('it ignores a reign that had already ended before the show date', function () {
    // Arrange
    $title = Title::factory()->active()->create();
    $formerChampion = Wrestler::factory()->create();
    $championOnShowDate = Wrestler::factory()->create();
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler($formerChampion)
        ->wonOn('2026-02-01 00:00:00')
        ->lostOn('2026-05-01 00:00:00')
        ->create();
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler($championOnShowDate)
        ->wonOn('2026-05-01 00:00:00')
        ->current()
        ->create();
    $event = Event::factory()->create(['date' => '2026-06-01 19:00:00']);

    // Act
    $validator = Validator::make([
        'competitors' => [['wrestlers' => [$championOnShowDate->id]]],
        'titles' => [$title->id],
    ], ['titles.*' => [new CurrentChampionIsCompeting($event)]]);

    // Assert
    expect($validator->passes())->toBeTrue();
});

test('it asks for no champion when the title had none on the show date', function () {
    // Arrange
    $title = Title::factory()->active()->create();
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler(Wrestler::factory()->create())
        ->wonOn('2026-05-01 00:00:00')
        ->current()
        ->create();
    $event = Event::factory()->create(['date' => '2026-03-10 19:00:00']);

    // Act
    $validator = Validator::make([
        'competitors' => [['wrestlers' => [Wrestler::factory()->create()->id]]],
        'titles' => [$title->id],
    ], ['titles.*' => [new CurrentChampionIsCompeting($event)]]);

    // Assert
    expect($validator->passes())->toBeTrue();
});

test('it keeps the current champion for an unscheduled event', function () {
    // Arrange
    $title = Title::factory()->active()->create();
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler(Wrestler::factory()->create())
        ->current()
        ->create();
    $event = Event::factory()->unscheduled()->create();

    // Act
    $validator = Validator::make([
        'competitors' => [['wrestlers' => [Wrestler::factory()->create()->id]]],
        'titles' => [$title->id],
    ], ['titles.*' => [new CurrentChampionIsCompeting($event)]]);

    // Assert
    expect($validator->errors()->has('titles.0'))->toBeTrue();
});
