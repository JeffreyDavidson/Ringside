<?php

declare(strict_types=1);

use App\Enums\Titles\TitleType;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Rules\Titles\MatchesCompetitorType;
use Illuminate\Support\Facades\Validator;

test('it accepts a singles title contested only by wrestlers', function () {
    $title = Title::factory()->create(['type' => TitleType::Singles]);
    $wrestler = Wrestler::factory()->create();

    $validator = Validator::make([
        'competitors' => [['wrestlers' => [$wrestler->id], 'tag_teams' => []]],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('it accepts a tag team title contested only by tag teams', function () {
    $title = Title::factory()->create(['type' => TitleType::TagTeam]);
    $tagTeam = TagTeam::factory()->create();

    $validator = Validator::make([
        'competitors' => [['wrestlers' => [], 'tag_teams' => [$tagTeam->id]]],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('it rejects a singles title contested by tag teams', function () {
    $title = Title::factory()->create(['type' => TitleType::Singles]);
    $tagTeam = TagTeam::factory()->create();

    $validator = Validator::make([
        'competitors' => [['wrestlers' => [], 'tag_teams' => [$tagTeam->id]]],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    expect($validator->errors()->first('titles.0'))
        ->toBe("The {$title->name} may only be contested by wrestlers.");
});

test('it rejects a tag team title contested by wrestlers', function () {
    $title = Title::factory()->create(['type' => TitleType::TagTeam]);
    $wrestler = Wrestler::factory()->create();

    $validator = Validator::make([
        'competitors' => [['wrestlers' => [$wrestler->id], 'tag_teams' => []]],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    expect($validator->errors()->first('titles.0'))
        ->toBe("The {$title->name} may only be contested by tag teams.");
});

test('it rejects mixed competitor types for one title', function () {
    $title = Title::factory()->create(['type' => TitleType::Singles]);
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();

    $validator = Validator::make([
        'competitors' => [[
            'wrestlers' => [$wrestler->id],
            'tag_teams' => [$tagTeam->id],
        ]],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    expect($validator->errors()->has('titles.0'))->toBeTrue();
});

test('it safely rejects a missing title', function () {
    $validator = Validator::make([
        'competitors' => [['wrestlers' => [1], 'tag_teams' => []]],
        'titles' => [PHP_INT_MAX],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    expect($validator->errors()->first('titles.0'))->toBe('The selected title is invalid.');
});

it('rejects a title identifier that is not numeric', function (mixed $value) {
    $validator = Validator::make([
        'competitors' => [['wrestlers' => [1], 'tag_teams' => []]],
        'titles' => [$value],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    $message = $validator->errors()->first('titles.0');

    expect($message)->toBe('The selected title is invalid.');
})->with([
    'text' => ['champion'],
    'array' => [[1]],
    'boolean' => [true],
]);

it('rejects a title when the competitors are malformed', function (mixed $competitors) {
    $title = Title::factory()->create(['type' => TitleType::Singles]);

    $validator = Validator::make([
        'competitors' => $competitors,
        'titles' => [$title->id],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    $message = $validator->errors()->first('titles.0');

    expect($message)->toBe("The {$title->name} may only be contested by wrestlers.");
})->with([
    'competitors is not a list' => 'wrestlers',
    'a side is not an array' => [['not-a-side']],
    'a side has no competitors' => [[['wrestlers' => []]]],
]);

it('accepts the expected competitors on a later side after skipping malformed sides', function () {
    $title = Title::factory()->create(['type' => TitleType::Singles]);
    $wrestler = Wrestler::factory()->create();

    $validator = Validator::make([
        'competitors' => [
            'not-a-side',
            ['wrestlers' => [$wrestler->id]],
        ],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    $passes = $validator->passes();

    expect($passes)->toBeTrue();
});

it('rejects a title when any side holds more than one competitor of its type', function (TitleType $type, array $competitors) {
    // Arrange
    $title = Title::factory()->create(['type' => $type]);

    // Act
    $validator = Validator::make([
        'competitors' => $competitors,
        'titles' => [$title->id],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    // Assert
    expect($validator->errors()->first('titles.0'))
        ->toBe("The {$title->name} needs exactly one competitor of its type on each side.");
})->with([
    'handicap with a singles title' => [TitleType::Singles, [['wrestlers' => [1, 2]], ['wrestlers' => [3]]]],
    'six-man tag with a singles title' => [TitleType::Singles, [['wrestlers' => [1, 2, 3]], ['wrestlers' => [4, 5, 6]]]],
    'eight-man tag with a tag title' => [TitleType::TagTeam, [['tag_teams' => [1, 2]], ['tag_teams' => [3, 4]]]],
]);

it('ignores empty sides when checking one competitor per side', function () {
    // Arrange
    $title = Title::factory()->create(['type' => TitleType::Singles]);

    // Act
    $validator = Validator::make([
        'competitors' => [['wrestlers' => [1]], ['wrestlers' => []], ['wrestlers' => [2]]],
        'titles' => [$title->id],
    ], [
        'titles.*' => [new MatchesCompetitorType],
    ]);

    // Assert
    expect($validator->passes())->toBeTrue();
});
