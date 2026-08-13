<?php

declare(strict_types=1);

use App\Exceptions\Titles\CannotBeRetiredException;
use App\Exceptions\Titles\CannotBeUnretiredException;
use App\Lifecycle\TitleLifecycleEligibility;
use App\Models\Titles\Title;

describe('title lifecycle eligibility', function () {
    test('keeps the retirement predicate aligned with its guard', function (string $factoryState, bool $canBeRetired) {
        $eligibility = new TitleLifecycleEligibility();
        $title = Title::factory()->{$factoryState}()->create();

        expect($eligibility->canRetire($title))->toBe($canBeRetired);

        if ($canBeRetired) {
            expect(fn () => $eligibility->ensureCanRetire($title))
                ->not->toThrow(CannotBeRetiredException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanRetire($title))
            ->toThrow(CannotBeRetiredException::class);
    })->with([
        'active' => ['active', true],
        'inactive' => ['inactive', true],
        'undebuted' => ['undebuted', false],
        'unactivated' => ['unactivated', false],
        'future debut' => ['withFutureDebut', false],
        'retired' => ['retired', false],
    ]);

    test('rejects unretiring a deleted retired title consistently', function () {
        $eligibility = new TitleLifecycleEligibility();
        $title = Title::factory()->retired()->create();
        $title->delete();

        expect($eligibility->canUnretire($title))->toBeFalse()
            ->and(fn () => $eligibility->ensureCanUnretire($title))
            ->toThrow(
                CannotBeUnretiredException::class,
                CannotBeUnretiredException::deleted($title)->getMessage(),
            );
    });

    test('keeps the unretirement predicate aligned with its guard', function (string $factoryState, bool $canBeUnretired) {
        $eligibility = new TitleLifecycleEligibility();
        $title = Title::factory()->{$factoryState}()->create();

        expect($eligibility->canUnretire($title))->toBe($canBeUnretired);

        if ($canBeUnretired) {
            expect(fn () => $eligibility->ensureCanUnretire($title))
                ->not->toThrow(CannotBeUnretiredException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanUnretire($title))
            ->toThrow(
                CannotBeUnretiredException::class,
                CannotBeUnretiredException::notRetired($title)->getMessage(),
            );
    })->with([
        'retired' => ['retired', true],
        'not retired' => ['active', false],
    ]);
});
