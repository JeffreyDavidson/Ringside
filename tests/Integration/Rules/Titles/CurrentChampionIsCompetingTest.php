<?php

declare(strict_types=1);

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
