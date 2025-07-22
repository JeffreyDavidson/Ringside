<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('active titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $activeTitles = Title::active()->get();

    expect($activeTitles)
        ->toHaveCount(1)
        ->collectionHas($activeTitle);
});

test('future activated titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $futureActivatedTitles = Title::withPendingDebut()->get();

    expect($futureActivatedTitles)
        ->toHaveCount(1)
        ->collectionHas($futureActivatedTitle);
});

test('inactive titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $inactiveTitles = Title::inactive()->get();

    expect($inactiveTitles)
        ->toHaveCount(3)
        ->collectionHas($inactiveTitle)
        ->collectionHas($retiredTitle)
        ->collectionHas($futureActivatedTitle);
});

test('retired titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $retiredTitles = Title::retired()->get();

    expect($retiredTitles)
        ->toHaveCount(1)
        ->collectionHas($retiredTitle);
});
