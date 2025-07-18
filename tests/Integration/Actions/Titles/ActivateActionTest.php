<?php

declare(strict_types=1);

use App\Actions\Titles\ActivateAction;
use App\Exceptions\Status\CannotBeActivatedException;
use App\Exceptions\Status\CannotBeDebutedException;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it activates an unactivated title at the current datetime by default', function () {
    $title = Title::factory()->unactivated()->create();
    $datetime = now();

    // Verify title is initially unactivated
    expect($title->isCurrentlyActive())->toBeFalse();
    expect($title->hasActivityPeriods())->toBeFalse();

    // Execute the activation
    resolve(ActivateAction::class)->handle($title);

    // Verify the title is now active and has activity periods
    $refreshedTitle = $title->fresh();
    expect($refreshedTitle->isCurrentlyActive())->toBeTrue();
    expect($refreshedTitle->hasActivityPeriods())->toBeTrue();
    
    // Verify the debut was created with correct datetime
    $activityPeriod = $refreshedTitle->currentActivityPeriod;
    expect($activityPeriod)->not->toBeNull();
    expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it activates an inactive title at the current datetime by default', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now();

    // Verify title is initially inactive but has activity periods
    expect($title->isCurrentlyActive())->toBeFalse();
    expect($title->hasActivityPeriods())->toBeTrue();
    expect($title->isInactive())->toBeTrue();

    // Execute the activation
    resolve(ActivateAction::class)->handle($title);

    // Verify the title is now active
    $refreshedTitle = $title->fresh();
    expect($refreshedTitle->isCurrentlyActive())->toBeTrue();
    expect($refreshedTitle->isInactive())->toBeFalse();
    
    // Verify the reinstatement was created with correct datetime
    $activityPeriod = $refreshedTitle->currentActivityPeriod;
    expect($activityPeriod)->not->toBeNull();
    expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it activates an unactivated title at a specific datetime', function () {
    $title = Title::factory()->unactivated()->create();
    $datetime = now()->addDays(2);

    // Verify title is initially unactivated
    expect($title->isCurrentlyActive())->toBeFalse();
    expect($title->hasActivityPeriods())->toBeFalse();

    // Execute the activation with specific datetime
    resolve(ActivateAction::class)->handle($title, $datetime);

    // Verify the title has activity periods but is not currently active (future activation)
    $refreshedTitle = $title->fresh();
    expect($refreshedTitle->hasActivityPeriods())->toBeTrue();
    expect($refreshedTitle->hasFutureActivity())->toBeTrue();
    expect($refreshedTitle->isCurrentlyActive())->toBeFalse(); // Future date, so not currently active
    
    // Verify the debut was created with the specific datetime
    $activityPeriod = $refreshedTitle->futureActivityPeriod;
    expect($activityPeriod)->not->toBeNull();
    expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it activates an inactive title at a specific datetime', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now()->addDays(2);

    // Verify title is initially inactive but has activity periods
    expect($title->isCurrentlyActive())->toBeFalse();
    expect($title->hasActivityPeriods())->toBeTrue();
    expect($title->isInactive())->toBeTrue();

    // Execute the activation with specific datetime
    resolve(ActivateAction::class)->handle($title, $datetime);

    // Verify the title has future activity but is not currently active (future date)
    $refreshedTitle = $title->fresh();
    expect($refreshedTitle->hasActivityPeriods())->toBeTrue();
    expect($refreshedTitle->hasFutureActivity())->toBeTrue();
    expect($refreshedTitle->isCurrentlyActive())->toBeFalse(); // Future date, so not currently active
    expect($refreshedTitle->isInactive())->toBeTrue(); // isInactive() = !isCurrentlyActive(), so still inactive until future date
    
    // Verify the reinstatement was created with the specific datetime
    $activityPeriod = $refreshedTitle->futureActivityPeriod;
    expect($activityPeriod)->not->toBeNull();
    expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it activates a retired title at the current datetime by default', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now();

    // Verify title is initially retired
    expect($title->isRetired())->toBeTrue();
    expect($title->isCurrentlyActive())->toBeFalse();
    expect($title->hasActivityPeriods())->toBeTrue();

    // Execute the activation (should unretire then reinstate)
    resolve(ActivateAction::class)->handle($title);

    // Verify the title is now active and no longer retired
    $refreshedTitle = $title->fresh();
    expect($refreshedTitle->isCurrentlyActive())->toBeTrue();
    expect($refreshedTitle->isRetired())->toBeFalse();
    
    // Verify the reinstatement was created with correct datetime
    $activityPeriod = $refreshedTitle->currentActivityPeriod;
    expect($activityPeriod)->not->toBeNull();
    expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
    
    // Verify retirement was ended
    $latestRetirement = $refreshedTitle->previousRetirement;
    expect($latestRetirement)->not->toBeNull();
    expect($latestRetirement->ended_at)->not->toBeNull();
    expect($latestRetirement->ended_at->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it activates a retired title at a specific datetime', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now()->addDays(2);

    // Verify title is initially retired
    expect($title->isRetired())->toBeTrue();
    expect($title->isCurrentlyActive())->toBeFalse();
    expect($title->hasActivityPeriods())->toBeTrue();

    // Execute the activation with specific datetime (should unretire then reinstate)
    resolve(ActivateAction::class)->handle($title, $datetime);

    // Verify the title has future activity but is not currently active (future date) and no longer retired
    $refreshedTitle = $title->fresh();
    expect($refreshedTitle->hasActivityPeriods())->toBeTrue();
    expect($refreshedTitle->hasFutureActivity())->toBeTrue();
    expect($refreshedTitle->isCurrentlyActive())->toBeFalse(); // Future date, so not currently active
    expect($refreshedTitle->isRetired())->toBeFalse();
    
    // Verify the reinstatement was created with the specific datetime
    $activityPeriod = $refreshedTitle->futureActivityPeriod;
    expect($activityPeriod)->not->toBeNull();
    expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
    
    // Verify retirement was ended with the specific datetime
    $latestRetirement = $refreshedTitle->previousRetirement;
    expect($latestRetirement)->not->toBeNull();
    expect($latestRetirement->ended_at)->not->toBeNull();
    expect($latestRetirement->ended_at->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it throws exception for activating a non activatable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    // Execute the activation and expect it to throw an exception
    resolve(ActivateAction::class)->handle($title);
})->throws(CannotBeActivatedException::class)->with([
    'active',
]);

test('it successfully activates a title with future activation', function () {
    $title = Title::factory()->withFutureActivation()->create();
    $datetime = now();

    // Verify title has future activity initially
    expect($title->hasFutureActivity())->toBeTrue();
    expect($title->isCurrentlyActive())->toBeFalse();

    // Execute the activation (should reinstate immediately, overriding future activation)
    resolve(ActivateAction::class)->handle($title);

    // Verify the title is now currently active
    $refreshedTitle = $title->fresh();
    expect($refreshedTitle->isCurrentlyActive())->toBeTrue();
    expect($refreshedTitle->hasActivityPeriods())->toBeTrue();
});

test('title can be debuted when in correct state', function () {
    $undebutedTitle = Title::factory()->unactivated()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $activeTitle = Title::factory()->active()->create();

    expect($undebutedTitle->canBeDebuted())->toBeTrue();
    expect($inactiveTitle->canBeDebuted())->toBeFalse();
    expect($activeTitle->canBeDebuted())->toBeFalse();
});

test('title can be reinstated when in correct state', function () {
    $inactiveTitle = Title::factory()->inactive()->create();
    $activeTitle = Title::factory()->active()->create();

    expect($inactiveTitle->canBeReinstated())->toBeTrue();
    expect($activeTitle->canBeReinstated())->toBeFalse();
});

test('title debut validation throws correct exceptions', function () {
    $activeTitle = Title::factory()->active()->create();
    $undebutedTitle = Title::factory()->unactivated()->create();

    expect(fn () => $activeTitle->ensureCanBeDebuted())
        ->toThrow(CannotBeDebutedException::class);
    expect(fn () => $undebutedTitle->ensureCanBeDebuted())
        ->not()->toThrow(Exception::class);
});

test('title reinstatement validation throws correct exceptions', function () {
    $inactiveTitle = Title::factory()->inactive()->create();
    $activeTitle = Title::factory()->active()->create();

    expect(fn () => $inactiveTitle->ensureCanBeReinstated())->not()->toThrow(Exception::class);
    expect(fn () => $activeTitle->ensureCanBeReinstated())
        ->toThrow(CannotBeActivatedException::class);
});
