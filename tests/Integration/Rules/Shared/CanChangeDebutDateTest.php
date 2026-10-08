<?php

declare(strict_types=1);

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use App\Models\Titles\Title;
use App\Rules\Shared\CanChangeDebutDate;

test('allows a debut date when no model is being edited', function () {
    $failed = false;

    new CanChangeDebutDate(null)->validate(
        'debut_date',
        now(),
        validationFailureCallback(function () use (&$failed): void {
            $failed = true;
        }),
    );

    expect($failed)->toBeFalse();
});

test('allows changing the date of an inactive model', function (string $modelClass) {
    $model = $modelClass::factory()->create();
    $failed = false;

    new CanChangeDebutDate($model)->validate(
        'debut_date',
        now(),
        validationFailureCallback(function () use (&$failed): void {
            $failed = true;
        }),
    );

    expect($failed)->toBeFalse();
})->with([
    'stable' => Stable::class,
    'title' => Title::class,
]);

test('allows retaining the current activity start date', function (string $modelClass) {
    $startedAt = now()->subWeek();
    $model = $modelClass::factory()
        ->has(ActivityPeriod::factory()->started($startedAt), 'activityPeriods')
        ->create();
    $failed = false;

    new CanChangeDebutDate($model)->validate(
        'debut_date',
        $startedAt->toDateString(),
        validationFailureCallback(function () use (&$failed): void {
            $failed = true;
        }),
    );

    expect($failed)->toBeFalse();
})->with([
    'stable' => Stable::class,
    'title' => Title::class,
]);

test('rejects changing the start date of an active model', function (string $modelClass) {
    $model = $modelClass::factory()
        ->has(ActivityPeriod::factory()->started(now()->subWeek()), 'activityPeriods')
        ->create();
    $message = null;

    new CanChangeDebutDate($model)->validate(
        'debut_date',
        now(),
        validationFailureCallback(function (string $failure) use (&$message): void {
            $message = $failure;
        }),
    );

    expect($message)->toBe("The debut date cannot be changed while {$model->name} is currently active.");
})->with([
    'stable' => Stable::class,
    'title' => Title::class,
]);

test('rejects an invalid debut date value for an active model', function () {
    $stable = Stable::factory()
        ->has(ActivityPeriod::factory()->started(now()->subWeek()), 'activityPeriods')
        ->create();
    $message = null;

    new CanChangeDebutDate($stable)->validate(
        'debut_date',
        [],
        validationFailureCallback(function (string $failure) use (&$message): void {
            $message = $failure;
        }),
    );

    expect($message)->toBe('The debut date must be a valid date.');
});

test('compares a reunited model against its first period rather than the current one', function (string $modelClass) {
    $debut = now()->subMonths(3);
    $model = $modelClass::factory()->create();
    $model->activityPeriods()->create(['started_at' => $debut, 'ended_at' => now()->subMonths(2)]);
    $model->activityPeriods()->create(['started_at' => now()->subMonth()]);
    $model->refresh();
    $failed = false;

    new CanChangeDebutDate($model)->validate(
        'debut_date',
        $debut->toDateString(),
        validationFailureCallback(function () use (&$failed): void {
            $failed = true;
        }),
    );

    expect($failed)->toBeFalse();
})->with([
    'stable' => Stable::class,
    'title' => Title::class,
]);

test('rejects changing the start date of a disbanded stable with several periods but allows keeping it', function (string $target, bool $fails) {
    // Arrange
    $stable = Stable::factory()->create(['name' => 'Reunited Stable']);
    $stable->activityPeriods()->create(['started_at' => '2020-01-01', 'ended_at' => '2021-01-01']);
    $stable->activityPeriods()->create(['started_at' => '2022-01-01', 'ended_at' => '2023-01-01']);
    $message = null;

    // Act
    new CanChangeDebutDate($stable)->validate(
        'started_at',
        $target,
        validationFailureCallback(function (string $failure) use (&$message): void {
            $message = $failure;
        }),
    );

    // Assert
    expect($message)->toBe($fails ? 'The debut date cannot be changed because Reunited Stable has been active in more than one period.' : null);
})->with([
    'a date after the first period ends' => ['2021-06-01', true],
    'a date within the first period' => ['2020-06-01', true],
    'the existing start date' => ['2020-01-01', false],
]);
