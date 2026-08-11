<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

describe('individual retirement validation', function () {
    test('keeps the retirement predicate aligned with its guard', function (string $factoryState, bool $canBeRetired) {
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($wrestler->canBeRetired())->toBe($canBeRetired);

        if ($canBeRetired) {
            expect(fn () => $wrestler->ensureCanBeRetired())
                ->not->toThrow(CannotBeRetiredException::class);

            return;
        }

        expect(fn () => $wrestler->ensureCanBeRetired())
            ->toThrow(CannotBeRetiredException::class);
    })->with([
        'employed' => ['employed', true],
        'suspended' => ['suspended', true],
        'injured' => ['injured', true],
        'released' => ['released', true],
        'unemployed' => ['unemployed', false],
        'future employment' => ['withFutureEmployment', false],
        'retired' => ['retired', false],
    ]);

    test('supports each individual roster model', function (string $modelClass) {
        $individual = $modelClass::factory()->employed()->create();

        expect($individual->canBeRetired())->toBeTrue()
            ->and(fn () => $individual->ensureCanBeRetired())
            ->not->toThrow(CannotBeRetiredException::class);
    })->with([
        Wrestler::class,
        Manager::class,
        Referee::class,
    ]);

    test('rejects unretiring a deleted retired individual consistently', function () {
        $wrestler = Wrestler::factory()->retired()->create();
        $wrestler->delete();

        expect($wrestler->canBeUnretired())->toBeFalse()
            ->and(fn () => $wrestler->ensureCanBeUnretired())
            ->toThrow(
                CannotBeUnretiredException::class,
                CannotBeUnretiredException::deleted($wrestler)->getMessage(),
            );
    });

    test('keeps the unretirement predicate aligned with its guard', function (string $factoryState, bool $canBeUnretired) {
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($wrestler->canBeUnretired())->toBe($canBeUnretired);

        if ($canBeUnretired) {
            expect(fn () => $wrestler->ensureCanBeUnretired())
                ->not->toThrow(CannotBeUnretiredException::class);

            return;
        }

        expect(fn () => $wrestler->ensureCanBeUnretired())
            ->toThrow(
                CannotBeUnretiredException::class,
                CannotBeUnretiredException::notRetired($wrestler)->getMessage(),
            );
    })->with([
        'retired' => ['retired', true],
        'not retired' => ['employed', false],
    ]);
});
