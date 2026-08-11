<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Models\Wrestlers\Wrestler;

test('keeps the employment predicate aligned with its guard', function (string $factoryState, bool $canBeEmployed) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    expect($wrestler->canBeEmployed())->toBe($canBeEmployed);

    if ($canBeEmployed) {
        expect(fn () => $wrestler->ensureCanBeEmployed())
            ->not->toThrow(CannotBeEmployedException::class);

        return;
    }

    expect(fn () => $wrestler->ensureCanBeEmployed())
        ->toThrow(CannotBeEmployedException::class);
})->with([
    'unemployed' => ['unemployed', true],
    'released' => ['released', true],
    'employed' => ['employed', false],
    'future employment' => ['withFutureEmployment', false],
    'retired' => ['retired', false],
]);
