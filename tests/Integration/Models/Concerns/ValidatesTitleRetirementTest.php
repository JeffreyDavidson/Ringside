<?php

declare(strict_types=1);

use App\Exceptions\Titles\CannotBeRetiredException;
use App\Exceptions\Titles\CannotBeUnretiredException;
use App\Models\Titles\Title;

describe('title retirement validation', function () {
    test('keeps the retirement predicate aligned with its guard', function (string $factoryState, bool $canBeRetired) {
        $title = Title::factory()->{$factoryState}()->create();

        expect($title->canBeRetired())->toBe($canBeRetired);

        if ($canBeRetired) {
            expect(fn () => $title->ensureCanBeRetired())
                ->not->toThrow(CannotBeRetiredException::class);

            return;
        }

        expect(fn () => $title->ensureCanBeRetired())
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
        $title = Title::factory()->retired()->create();
        $title->delete();

        expect($title->canBeUnretired())->toBeFalse()
            ->and(fn () => $title->ensureCanBeUnretired())
            ->toThrow(
                CannotBeUnretiredException::class,
                CannotBeUnretiredException::deleted($title)->getMessage(),
            );
    });

    test('keeps the unretirement predicate aligned with its guard', function (string $factoryState, bool $canBeUnretired) {
        $title = Title::factory()->{$factoryState}()->create();

        expect($title->canBeUnretired())->toBe($canBeUnretired);

        if ($canBeUnretired) {
            expect(fn () => $title->ensureCanBeUnretired())
                ->not->toThrow(CannotBeUnretiredException::class);

            return;
        }

        expect(fn () => $title->ensureCanBeUnretired())
            ->toThrow(
                CannotBeUnretiredException::class,
                CannotBeUnretiredException::notRetired($title)->getMessage(),
            );
    })->with([
        'retired' => ['retired', true],
        'not retired' => ['active', false],
    ]);
});
