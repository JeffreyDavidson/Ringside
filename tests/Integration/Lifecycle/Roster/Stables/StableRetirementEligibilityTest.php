<?php

declare(strict_types=1);

use App\Actions\Stables\DisbandAction;
use App\Actions\Stables\RetireAction;
use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
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

test('a stable disbanded before it retired can be unretired without a headcount check', function (): void {
    // Arrange
    $this->travelTo(now()->startOfSecond());
    $stable = Stable::factory()->active()->create();
    resolve(DisbandAction::class)->handle($stable, now()->subHours(2));
    resolve(RetireAction::class)->handle($stable, now()->subHour());
    $eligibility = resolve(StableRetirementEligibility::class);

    // Act
    $canUnretire = $eligibility->canUnretire($stable);

    // Assert
    expect($canUnretire)->toBeTrue()
        ->and(fn () => $eligibility->ensureCanUnretire($stable))->not->toThrow(CannotBeUnretiredException::class);
});

test('a stable active before it retired still needs three available members to unretire', function (): void {
    // Arrange
    $this->travelTo(now()->startOfSecond());
    $stable = Stable::factory()->active()->create();
    $stable->tagTeams()->detach();
    resolve(RetireAction::class)->handle($stable, now());
    $eligibility = resolve(StableRetirementEligibility::class);

    // Act
    $canUnretire = $eligibility->canUnretire($stable);

    // Assert
    expect($canUnretire)->toBeFalse();
});
