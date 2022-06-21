<?php

use App\Enums\TitleStatus;
use App\Models\Concerns\HasRetirements;
use App\Models\Title;

test('a title has a name', function () {
    $title = Title::factory()->create(['name' => 'Example Name Title']);

    expect($title)->name->toBe('Example Name Title');
});

test('a title has a status', function () {
    $title = Title::factory()->create();

    expect($title)->status->toBeInstanceOf(TitleStatus::class);
});

test('a title uses soft deleted trait', function () {
    expect(Title::class)->assertUsesTrait(SoftDeletes::class);
});

test('a title uses activation trait', function () {
    expect(Title::class)->assertUsesTrait(Activations::class);
});

test('a title uses competable trait', function () {
    expect(Title::class)->assertUsesTrait(Competable::class);
});

test('a title uses retirements trait', function () {
    expect(Title::class)->assertUsesTrait(HasRetirements::class);
});

test('active titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $activeTitles = Title::active()->get();

    expect($activeTitles)
        ->toHaveCount(1)
        ->assertCollectionHas($activeTitle);
});

test('future activated titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $futureActivatedTitles = Title::withFutureActivation()->get();

    expect($futureActivatedTitles)
        ->toHaveCount(1)
        ->assertCollectionHas($futureActivatedTitle);
});

test('inactive titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $inactiveTitles = Title::inactive()->get();

    expect($inactiveTitles)
        ->toHaveCount(1)
        ->assertCollectionHas($inactiveTitle);
});

test('retired titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $retiredTitles = Title::retired()->get();

    expect($retiredTitles)
        ->toHaveCount(1)
        ->assertCollectionHas($retiredTitle);
});
