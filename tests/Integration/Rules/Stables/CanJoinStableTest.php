<?php

declare(strict_types=1);

use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Stables\CanJoinStable;
use Illuminate\Support\Facades\Validator;

test('it reports an unknown stable member as a validation error', function () {
    $validator = Validator::make(
        ['member' => 999],
        ['member' => [new CanJoinStable(Wrestler::class)]],
    );

    expect($validator->errors()->has('member'))->toBeTrue();
});

it('rejects a selected member that is not a scalar identifier', function (mixed $value) {
    $validator = Validator::make(
        ['member' => $value],
        ['member' => [new CanJoinStable(Wrestler::class)]],
    );

    $message = $validator->errors()->first('member');

    expect($message)->toBe('The selected stable member is invalid.');
})->with([
    'array' => [[1]],
    'boolean' => [true],
    'float' => [1.5],
]);

it('rejects a suspended wrestler joining a stable', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $validator = Validator::make(
        ['member' => $wrestler->id],
        ['member' => [new CanJoinStable(Wrestler::class)]],
    );

    $message = $validator->errors()->first('member');

    expect($message)->toBe('This member is suspended and cannot join the stable.');
});

it('accepts an employed wrestler who is not suspended or in another stable', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $validator = Validator::make(
        ['member' => $wrestler->id],
        ['member' => [new CanJoinStable(Wrestler::class)]],
    );

    $passes = $validator->passes();

    expect($passes)->toBeTrue();
});

it('is misconfigured for a model that cannot be a stable member', function () {
    $referee = Referee::factory()->employed()->create();
    $rule = app()->make(CanJoinStable::class, ['memberClass' => Referee::class]);
    $validator = Validator::make(
        ['member' => $referee->id],
        ['member' => [$rule]],
    );

    $validate = fn (): bool => $validator->passes();

    expect($validate)->toThrow(LogicException::class, Referee::class.' must be an employable, suspendable Stable member.');
});
