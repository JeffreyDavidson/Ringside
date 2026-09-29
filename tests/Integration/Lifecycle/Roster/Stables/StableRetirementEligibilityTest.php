<?php

declare(strict_types=1);

use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Lifecycle\Roster\Stables\StableRetirementEligibility;
use App\Models\Roster\Stables\Stable;

test('retirement predicate stays aligned with its guard', function (string $factoryState, bool $canRetire) {
    $stable = Stable::factory()->{$factoryState}()->create();
    $eligibility = resolve(StableRetirementEligibility::class);

    expect($eligibility->canRetire($stable))->toBe($canRetire);

    if ($canRetire) {
        expect(fn () => $eligibility->ensureCanRetire($stable))->not->toThrow(CannotBeRetiredException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanRetire($stable))->toThrow(CannotBeRetiredException::class);
})->with([
    'active' => ['active', true],
    'inactive' => ['inactive', true],
    'unactivated' => ['unactivated', false],
    'future activation' => ['withFutureActivation', false],
    'retired' => ['retired', false],
]);
