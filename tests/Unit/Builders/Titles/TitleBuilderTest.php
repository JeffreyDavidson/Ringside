<?php

declare(strict_types=1);

use App\Models\Titles\Title;

test('active titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $activeTitles = Title::query()->active()->get();

    expect($activeTitles)
        ->toHaveCount(1)
        ->and($activeTitles->contains($activeTitle))->toBeTrue();
});

test('future activated titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $futureActivatedTitles = Title::query()->withPendingDebut()->get();

    expect($futureActivatedTitles)
        ->toHaveCount(1)
        ->and($futureActivatedTitles->contains($futureActivatedTitle))->toBeTrue();
});

test('inactive titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $inactiveTitles = Title::query()->inactive()->get();

    expect($inactiveTitles)
        ->toHaveCount(2)
        ->and($inactiveTitles->contains($inactiveTitle))->toBeTrue()
        ->and($inactiveTitles->contains($retiredTitle))->toBeTrue()
        ->and($inactiveTitles->contains($futureActivatedTitle))->toBeFalse();
});

test('retired titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $retiredTitles = Title::query()->retired()->get();

    expect($retiredTitles)
        ->toHaveCount(1)
        ->and($retiredTitles->contains($retiredTitle))->toBeTrue();
});
