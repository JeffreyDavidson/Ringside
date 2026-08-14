<?php

declare(strict_types=1);

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Stables\Stable;
use App\Models\Titles\Title;
use App\Rules\Shared\CanChangeDebutDate;

test('allows a debut date when no model is being edited', function () {
    $failed = false;

    (new CanChangeDebutDate(null))->validate(
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

    (new CanChangeDebutDate($model))->validate(
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

    (new CanChangeDebutDate($model))->validate(
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

    (new CanChangeDebutDate($model))->validate(
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
