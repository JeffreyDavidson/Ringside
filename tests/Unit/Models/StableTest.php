<?php

use App\Enums\StableStatus;
use App\Models\Concerns\Activations;
use App\Models\Concerns\Deactivations;
use App\Models\Concerns\OwnedByUser;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Contracts\Retirable;
use App\Models\Stable;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a stable has a name', function () {
    $stable = Stable::factory()->create(['name' => 'Example Stable Name']);

    expect($stable)->name->toBe('Example Stable Name');
});

test('a stable has a status', function () {
    $stable = Stable::factory()->create();

    expect($stable)->status->toBeInstanceOf(StableStatus::class);
});

test('a stable uses soft deleted trait', function () {
    expect(Stable::class)->assertUsesTrait(SoftDeletes::class);
});

test('a stable uses activations trait', function () {
    expect(Stable::class)->assertUsesTrait(Activations::class);
});

test('a stable uses deactivations trait', function () {
    expect(Stable::class)->assertUsesTrait(Deactivations::class);
});

test('a stable uses owned by user trait', function () {
    expect(Stable::class)->assertUsesTrait(OwnedByUser::class);
});

test('a stable implements activatable interface', function () {
    expect(class_implements(Stable::class))->toContain(Activatable::class);
});

test('a stable implements deactivatable interface', function () {
    expect(class_implements(Stable::class))->toContain(Deactivatable::class);
});

test('a stable implements retirable interface', function () {
    expect(class_implements(Stable::class))->toContain(Retirable::class);
});

test('active stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $activeStables = Stable::active()->get();

    expect($activeStables)
        ->toHaveCount(1)
        ->collectionHas($activeStable);
});

test('future activated stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $futureActivatedStables = Stable::withFutureActivation()->get();

    expect($futureActivatedStables)
        ->toHaveCount(1)
        ->collectionHas($futureActivatedStable);
});

test('inactive stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $inactiveStables = Stable::inactive()->get();

    expect($inactiveStables)
        ->toHaveCount(1)
        ->collectionHas($inactiveStable);
});

test('retired stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $retiredStables = Stable::retired()->get();

    expect($retiredStables)
        ->toHaveCount(1)
        ->collectionHas($retiredStable);
});

test('unactivated stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $unactivatedStables = Stable::unactivated()->get();

    expect($unactivatedStables)
        ->toHaveCount(1)
        ->collectionHas($unactivatedStable);
});
