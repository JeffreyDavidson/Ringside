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
        'titles.*' => [new CurrentChampionIsCompeting()],
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
        'titles.*' => [new CurrentChampionIsCompeting()],
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
        'titles.*' => [new CurrentChampionIsCompeting()],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('it accepts a vacant title', function () {
    $title = Title::factory()->active()->create();

    $validator = Validator::make([
        'competitors' => [],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new CurrentChampionIsCompeting()],
    ]);

    expect($validator->passes())->toBeTrue();
});
