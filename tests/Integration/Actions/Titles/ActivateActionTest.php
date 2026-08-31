<?php

declare(strict_types=1);

use App\Actions\Titles\ActivateAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Exceptions\Titles\CannotBeDebutedException;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Lifecycle\Titles\TitleLifecycleEligibility;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it activates an unactivated title at the current datetime by default', function () {
    $title = Title::factory()->unactivated()->create();
    $datetime = now();

    // Verify title is initially unactivated
    expect($title->currentActivityPeriod()->exists())->toBeFalse();
    expect($title->activityPeriods()->exists())->toBeFalse();

    // Execute the activation
    resolve(ActivateAction::class)->handle($title);

    // Verify the title is now active and has activity periods
    $refreshedTitle = freshModel($title);
    expect($refreshedTitle->currentActivityPeriod()->exists())->toBeTrue();
    expect($refreshedTitle->activityPeriods()->exists())->toBeTrue();

    // Verify the debut was created with correct datetime
    $activityPeriod = $refreshedTitle->currentActivityPeriod()->firstOrFail();
    expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
    expect($refreshedTitle->lifecycleTransitions()->sole()->transition)->toBe(LifecycleTransitionType::Debuted);
});

test('it activates an inactive title at the current datetime by default', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now();

    // Verify title is initially inactive but has activity periods
    expect($title->currentActivityPeriod()->exists())->toBeFalse();
    expect($title->activityPeriods()->exists())->toBeTrue();
    expect($title->currentActivityPeriod()->exists())->toBeFalse();

    // Execute the activation
    resolve(ActivateAction::class)->handle($title);

    // Verify the title is now active
    $refreshedTitle = freshModel($title);
    expect($refreshedTitle->currentActivityPeriod()->exists())->toBeTrue();
    expect($refreshedTitle->currentActivityPeriod()->exists())->toBeTrue();

    // Verify the reinstatement was created with correct datetime
    $activityPeriod = $refreshedTitle->currentActivityPeriod()->firstOrFail();
    expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
    expect($refreshedTitle->lifecycleTransitions()->sole()->transition)->toBe(LifecycleTransitionType::Reinstated);
});

test('it activates an unactivated title at a specific datetime', function () {
    $title = Title::factory()->unactivated()->create();
    $datetime = now()->addDays(2);

    // Verify title is initially unactivated
    expect($title->currentActivityPeriod()->exists())->toBeFalse();
    expect($title->activityPeriods()->exists())->toBeFalse();

    // Execute the activation with specific datetime
    resolve(ActivateAction::class)->handle($title, $datetime);

    // Verify the title has activity periods but is not currently active (future activation)
    $refreshedTitle = freshModel($title);
    expect($refreshedTitle->activityPeriods()->exists())->toBeTrue();
    expect($refreshedTitle->futureActivityPeriod()->exists())->toBeTrue();
    expect($refreshedTitle->currentActivityPeriod()->exists())->toBeFalse(); // Future date, so not currently active

    // Verify the debut was created with the specific datetime
    $activityPeriod = $refreshedTitle->futureActivityPeriod()->firstOrFail();
    expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it activates an inactive title at a specific datetime', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now()->addDays(2);

    // Verify title is initially inactive but has activity periods
    expect($title->currentActivityPeriod()->exists())->toBeFalse();
    expect($title->activityPeriods()->exists())->toBeTrue();
    expect($title->currentActivityPeriod()->exists())->toBeFalse();

    // Execute the activation with specific datetime
    resolve(ActivateAction::class)->handle($title, $datetime);

    // Verify the title has future activity but is not currently active (future date)
    $refreshedTitle = freshModel($title);
    expect($refreshedTitle->activityPeriods()->exists())->toBeTrue();
    expect($refreshedTitle->futureActivityPeriod()->exists())->toBeTrue();
    expect($refreshedTitle->currentActivityPeriod()->exists())->toBeFalse(); // Future date, so not currently active
    expect($refreshedTitle->currentActivityPeriod()->exists())->toBeFalse(); // Future date, so still inactive until the period begins.

    // Verify the reinstatement was created with the specific datetime
    $activityPeriod = $refreshedTitle->futureActivityPeriod()->firstOrFail();
    expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it activates a retired title at the current datetime by default', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now();

    // Verify title is initially retired
    expect($title->currentRetirement()->exists())->toBeTrue();
    expect($title->currentActivityPeriod()->exists())->toBeFalse();
    expect($title->activityPeriods()->exists())->toBeTrue();

    // Execute the activation (should unretire then reinstate)
    resolve(ActivateAction::class)->handle($title);

    // Verify the title is now active and no longer retired
    $refreshedTitle = freshModel($title);
    expect($refreshedTitle->currentActivityPeriod()->exists())->toBeTrue();
    expect($refreshedTitle->currentRetirement()->exists())->toBeFalse();

    // Verify the reinstatement was created with correct datetime
    $activityPeriod = $refreshedTitle->currentActivityPeriod()->firstOrFail();
    expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));

    // Verify retirement was ended
    $latestRetirement = $refreshedTitle->previousRetirement()->firstOrFail();
    expect($latestRetirement->ended_at)->not()->toBeNull();
    expect(requiredDate($latestRetirement->ended_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it activates a retired title at a specific datetime', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now()->addDays(2);

    // Verify title is initially retired
    expect($title->currentRetirement()->exists())->toBeTrue();
    expect($title->currentActivityPeriod()->exists())->toBeFalse();
    expect($title->activityPeriods()->exists())->toBeTrue();

    // Execute the activation with specific datetime (should unretire then reinstate)
    resolve(ActivateAction::class)->handle($title, $datetime);

    // Verify the title has future activity but is not currently active (future date) and no longer retired
    $refreshedTitle = freshModel($title);
    expect($refreshedTitle->activityPeriods()->exists())->toBeTrue();
    expect($refreshedTitle->futureActivityPeriod()->exists())->toBeTrue();
    expect($refreshedTitle->currentActivityPeriod()->exists())->toBeFalse(); // Future date, so not currently active
    expect($refreshedTitle->currentRetirement()->exists())->toBeFalse();

    // Verify the reinstatement was created with the specific datetime
    $activityPeriod = $refreshedTitle->futureActivityPeriod()->firstOrFail();
    expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));

    // Verify retirement was ended with the specific datetime
    $latestRetirement = $refreshedTitle->previousRetirement()->firstOrFail();
    expect($latestRetirement->ended_at)->not()->toBeNull();
    expect(requiredDate($latestRetirement->ended_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it throws exception for activating a non activatable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    // Execute the activation and expect it to throw an exception
    resolve(ActivateAction::class)->handle($title);
})->throws(CannotBeReinstatedException::class)->with([
    'active',
]);

test('it successfully activates a title with future activation', function () {
    $title = Title::factory()->withFutureActivation()->create();
    $datetime = now();

    // Verify title has future activity initially
    expect($title->futureActivityPeriod()->exists())->toBeTrue();
    expect($title->currentActivityPeriod()->exists())->toBeFalse();

    // Execute the activation (should reinstate immediately, overriding future activation)
    resolve(ActivateAction::class)->handle($title);

    // Verify the title is now currently active
    $refreshedTitle = freshModel($title);
    expect($refreshedTitle->currentActivityPeriod()->exists())->toBeTrue();
    expect($refreshedTitle->activityPeriods()->exists())->toBeTrue();
});

test('title can be debuted when in correct state', function () {
    $eligibility = new TitleLifecycleEligibility();
    $undebutedTitle = Title::factory()->unactivated()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $activeTitle = Title::factory()->active()->create();

    expect($eligibility->allows($undebutedTitle, TitleLifecycleTransition::Debut))->toBeTrue();
    expect($eligibility->allows($inactiveTitle, TitleLifecycleTransition::Debut))->toBeFalse();
    expect($eligibility->allows($activeTitle, TitleLifecycleTransition::Debut))->toBeFalse();
});

test('title can be reinstated when in correct state', function () {
    $eligibility = new TitleLifecycleEligibility();
    $inactiveTitle = Title::factory()->inactive()->create();
    $activeTitle = Title::factory()->active()->create();

    expect($eligibility->allows($inactiveTitle, TitleLifecycleTransition::Reinstate))->toBeTrue();
    expect($eligibility->allows($activeTitle, TitleLifecycleTransition::Reinstate))->toBeFalse();
});

test('title debut validation throws correct exceptions', function () {
    $eligibility = new TitleLifecycleEligibility();
    $activeTitle = Title::factory()->active()->create();
    $undebutedTitle = Title::factory()->unactivated()->create();

    expect(fn () => $eligibility->ensureAllowed($activeTitle, TitleLifecycleTransition::Debut))
        ->toThrow(CannotBeDebutedException::class);
    expect(fn () => $eligibility->ensureAllowed($undebutedTitle, TitleLifecycleTransition::Debut))
        ->not()->toThrow(Exception::class);
});

test('title reinstatement validation throws correct exceptions', function () {
    $eligibility = new TitleLifecycleEligibility();
    $inactiveTitle = Title::factory()->inactive()->create();
    $activeTitle = Title::factory()->active()->create();

    expect(fn () => $eligibility->ensureAllowed($inactiveTitle, TitleLifecycleTransition::Reinstate))->not()->toThrow(Exception::class);
    expect(fn () => $eligibility->ensureAllowed($activeTitle, TitleLifecycleTransition::Reinstate))
        ->toThrow(CannotBeReinstatedException::class);
});
