<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeRestoredException;
use App\Lifecycle\IndividualDeletionEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

describe('individual restoration validation', function () {
    test('unsaved individuals cannot be restored', function (string $individualType) {
        $individual = match ($individualType) {
            Wrestler::class => Wrestler::factory()->make(),
            Manager::class => Manager::factory()->make(),
            Referee::class => Referee::factory()->make(),
            default => throw new InvalidArgumentException("Unsupported individual type [{$individualType}]."),
        };

        expect(resolve(IndividualDeletionEligibility::class)->canRestore($individual))->toBeFalse();
        expect(fn () => resolve(IndividualDeletionEligibility::class)->ensureCanRestore($individual))
            ->toThrow(CannotBeRestoredException::class);
    })->with([
        Wrestler::class,
        Manager::class,
        Referee::class,
    ]);

    test('predicate matches its guard for each individual state', function (string $individualType, string $state, bool $canBeRestored) {
        $individual = match ($individualType) {
            Wrestler::class => Wrestler::factory()->create(),
            Manager::class => Manager::factory()->create(),
            Referee::class => Referee::factory()->create(),
            default => throw new InvalidArgumentException("Unsupported individual type [{$individualType}]."),
        };

        if ($state === 'deleted') {
            $individual->delete();
        }

        expect(resolve(IndividualDeletionEligibility::class)->canRestore($individual))->toBe($canBeRestored);

        if ($canBeRestored) {
            expect(fn () => resolve(IndividualDeletionEligibility::class)->ensureCanRestore($individual))->not->toThrow(CannotBeRestoredException::class);

            return;
        }

        expect(fn () => resolve(IndividualDeletionEligibility::class)->ensureCanRestore($individual))
            ->toThrow(CannotBeRestoredException::class);
    })->with([
        'existing wrestler' => [Wrestler::class, 'existing', false],
        'deleted wrestler' => [Wrestler::class, 'deleted', true],
        'existing manager' => [Manager::class, 'existing', false],
        'deleted manager' => [Manager::class, 'deleted', true],
        'existing referee' => [Referee::class, 'existing', false],
        'deleted referee' => [Referee::class, 'deleted', true],
    ]);
});
