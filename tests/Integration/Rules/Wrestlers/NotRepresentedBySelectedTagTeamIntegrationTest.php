<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Wrestlers\NotRepresentedBySelectedTagTeam;
use Illuminate\Support\Facades\Validator;

test('it reports an unknown wrestler as a validation error', function () {
    $validator = Validator::make(
        ['wrestler' => 999],
        ['wrestler' => [new NotRepresentedBySelectedTagTeam(collect([1]))]],
    );

    expect($validator->errors()->has('wrestler'))->toBeTrue();
});

it('rejects a wrestler identifier that is not a scalar', function (mixed $value) {
    $validator = Validator::make(
        ['wrestler' => $value],
        ['wrestler' => [new NotRepresentedBySelectedTagTeam(collect([1]))]],
    );

    $message = $validator->errors()->first('wrestler');

    expect($message)->toBe('The selected wrestler is invalid.');
})->with([
    'array' => [[1]],
    'boolean' => [true],
    'float' => [1.5],
]);

it('accepts any wrestler when no tag team is selected', function () {
    $validator = Validator::make(
        ['wrestler' => [1]],
        ['wrestler' => [new NotRepresentedBySelectedTagTeam(collect())]],
    );

    $passes = $validator->passes();

    expect($passes)->toBeTrue();
});

it('rejects a wrestler already represented by a selected tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->onCurrentTagTeam($tagTeam)->create();
    $validator = Validator::make(
        ['wrestler' => $wrestler->id],
        ['wrestler' => [new NotRepresentedBySelectedTagTeam(collect([$tagTeam->id]))]],
    );

    $message = $validator->errors()->first('wrestler');

    expect($message)->toBe('This wrestler is already represented in the stable through their tag team.');
});

it('accepts a wrestler whose tag team is not selected', function () {
    $wrestler = Wrestler::factory()->onCurrentTagTeam()->create();
    $selectedTagTeam = TagTeam::factory()->create();
    $validator = Validator::make(
        ['wrestler' => $wrestler->id],
        ['wrestler' => [new NotRepresentedBySelectedTagTeam(collect([$selectedTagTeam->id]))]],
    );

    $passes = $validator->passes();

    expect($passes)->toBeTrue();
});
