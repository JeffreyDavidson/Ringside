<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Wrestlers\IsNotInjured;
use Illuminate\Support\Facades\Validator;

test('it reports an unknown wrestler as a validation error', function () {
    $validator = Validator::make(
        ['wrestler' => 999],
        ['wrestler' => [new IsNotInjured]],
    );

    expect($validator->errors()->has('wrestler'))->toBeTrue();
});

it('rejects a wrestler identifier that is not a scalar', function (mixed $value) {
    $validator = Validator::make(
        ['wrestler' => $value],
        ['wrestler' => [new IsNotInjured]],
    );

    $message = $validator->errors()->first('wrestler');

    expect($message)->toBe('The selected wrestler is invalid.');
})->with([
    'array' => [[1]],
    'boolean' => [true],
    'float' => [1.5],
]);

it('rejects an injured wrestler', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $validator = Validator::make(
        ['wrestler' => $wrestler->id],
        ['wrestler' => [new IsNotInjured]],
    );

    $message = $validator->errors()->first('wrestler');

    expect($message)->toBe("{$wrestler->name} is injured and cannot join the stable.");
});

it('accepts a wrestler who is not injured', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $validator = Validator::make(
        ['wrestler' => $wrestler->id],
        ['wrestler' => [new IsNotInjured]],
    );

    $passes = $validator->passes();

    expect($passes)->toBeTrue();
});
