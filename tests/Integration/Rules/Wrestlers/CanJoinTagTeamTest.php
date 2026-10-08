<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Wrestlers\CanJoinTagTeam;
use Illuminate\Support\Facades\Validator;

it('rejects a wrestler identifier that is not a scalar', function (mixed $value) {
    $validator = Validator::make(
        ['wrestler' => $value],
        ['wrestler' => [new CanJoinTagTeam]],
    );

    $message = $validator->errors()->first('wrestler');

    expect($message)->toBe('The selected wrestler is invalid.');
})->with([
    'array' => [[1]],
    'boolean' => [true],
    'float' => [1.5],
]);

it('rejects an unknown wrestler', function () {
    $validator = Validator::make(
        ['wrestler' => 999],
        ['wrestler' => [new CanJoinTagTeam]],
    );

    $message = $validator->errors()->first('wrestler');

    expect($message)->toBe('The selected wrestler is invalid.');
});

it('rejects a suspended or injured wrestler', function (string $state) {
    $wrestler = Wrestler::factory()->{$state}()->create();
    $validator = Validator::make(
        ['wrestler' => $wrestler->id],
        ['wrestler' => [new CanJoinTagTeam]],
    );

    $message = $validator->errors()->first('wrestler');

    expect($message)->toBe('This wrestler cannot join the tag team.');
})->with(['suspended', 'injured']);

it('rejects a wrestler who belongs to another tag team', function () {
    $wrestler = Wrestler::factory()->onCurrentTagTeam()->create();
    $validator = Validator::make(
        ['wrestler' => $wrestler->id],
        ['wrestler' => [new CanJoinTagTeam]],
    );

    $message = $validator->errors()->first('wrestler');

    expect($message)->toBe('This wrestler is already a member of another tag team.');
});

it('accepts a wrestler who already belongs to the tag team being edited', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->onCurrentTagTeam($tagTeam)->create();
    $validator = Validator::make(
        ['wrestler' => $wrestler->id],
        ['wrestler' => [new CanJoinTagTeam($tagTeam->id)]],
    );

    $passes = $validator->passes();

    expect($passes)->toBeTrue();
});

it('accepts an available wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $validator = Validator::make(
        ['wrestler' => $wrestler->id],
        ['wrestler' => [new CanJoinTagTeam]],
    );

    $passes = $validator->passes();

    expect($passes)->toBeTrue();
});
