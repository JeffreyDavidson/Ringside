<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Wrestlers\Wrestler;

describe('individual injury validation', function () {
    test('keeps the injury predicate aligned with its guard', function (string $factoryState, bool $canBeInjured) {
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($wrestler->canBeInjured())->toBe($canBeInjured);

        if ($canBeInjured) {
            expect(fn () => $wrestler->ensureCanBeInjured())
                ->not->toThrow(CannotBeInjuredException::class);

            return;
        }

        expect(fn () => $wrestler->ensureCanBeInjured())
            ->toThrow(CannotBeInjuredException::class);
    })->with([
        'employed' => ['employed', true],
        'unemployed' => ['unemployed', false],
        'released' => ['released', false],
        'retired' => ['retired', false],
        'future employment' => ['withFutureEmployment', false],
        'suspended' => ['suspended', false],
        'injured' => ['injured', false],
    ]);

    test('keeps healing predicates aligned with their guard', function (string $factoryState, bool $canBeHealed) {
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($wrestler->canBeHealed())->toBe($canBeHealed)
            ->and($wrestler->canBeClearedFromInjury())->toBe($canBeHealed);

        if ($canBeHealed) {
            expect(fn () => $wrestler->ensureCanBeHealed())
                ->not->toThrow(CannotBeClearedFromInjuryException::class);

            return;
        }

        expect(fn () => $wrestler->ensureCanBeHealed())
            ->toThrow(CannotBeClearedFromInjuryException::class);
    })->with([
        'injured' => ['injured', true],
        'not injured' => ['employed', false],
    ]);
});
