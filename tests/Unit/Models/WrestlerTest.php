<?php

use App\Enums\WrestlerStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\SingleRosterMember;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a wrestler has a name', function () {
    $wrestler = Wrestler::factory()->create(['name' => 'Example Wrestler Name']);

    expect($wrestler)->name->toBe('Example Wrestler Name');
});

test('a wrestler has a height', function () {
    $wrestler = Wrestler::factory()->create(['height' => 70]);

    expect($wrestler)->height->toBe(70);
});

test('a wrestler has a weight', function () {
    $wrestler = Wrestler::factory()->create(['weight' => 210]);

    expect($wrestler)->weight->toBe(210);
});

test('a wrestler has a hometown', function () {
    $wrestler = Wrestler::factory()->create(['hometown' => 'Los Angeles, California']);

    expect($wrestler)->hometown->toBe('Los Angeles, California');
});

test('a wrestler can have a signature move', function () {
    $wrestler = Wrestler::factory()->create(['signature_move' => 'Example Signature Move']);

    expect($wrestler)->signature_move->toBe('Example Signature Move');
});

test('a wrestler has a status', function () {
    $wrestler = Wrestler::factory()->create();

    expect($wrestler)->status->toBeInstanceOf(WrestlerStatus::class);
});

test('a wrestler is a single roster member', function () {
    expect(get_parent_class(Wrestler::class))->toBeInstanceOf(SingleRosterMember::class);
});

test('a wrestler uses soft deleted trait', function () {
    expect(Wrestler::class)->assertUsesTrait(SoftDeletes::class);
});

test('a wrestler uses can join stables trait', function () {
    expect(Wrestler::class)->assertUsesTrait(CanJoinStables::class);
});

test('a wrestler implements bookable interface', function () {
    expect(class_implements(Wrestler::class))->assertContains(Bookable::class);
});

test('a wrestler implements can be a stable member interface', function () {
    expect(class_implements(Wrestler::class))->assertContains(CanBeAStableMember::class);
});

test('bookable wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $bookableWrestlers = Wrestler::bookable()->get();

    expect($bookableWrestlers)
        ->toHaveCount(1)
        ->assertCollectionHas($bookableWrestler);
});

test('future employed wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $futureEmployedWrestlers = Wrestler::withFutureEmployment()->get();

    expect($futureEmployedWrestlers)
        ->toHaveCount(1)
        ->assertCollectionHas($futureEmployedWrestler);
});

test('suspended wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $suspendedWrestlers = Wrestler::suspended()->get();

    expect($suspendedWrestlers)
        ->toHaveCount(1)
        ->assertCollectionHas($suspendedWrestler);
});

test('released wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $releasedWrestlers = Wrestler::suspended()->get();

    expect($releasedWrestlers)
        ->toHaveCount(1)
        ->assertCollectionHas($releasedWrestler);
});

test('retired wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $retiredWrestlers = Wrestler::suspended()->get();

    expect($retiredWrestlers)
        ->toHaveCount(1)
        ->assertCollectionHas($retiredWrestler);
});

test('unemployed wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $unemployedWrestlers = Wrestler::unemployed()->get();

    expect($unemployedWrestlers)
        ->toHaveCount(1)
        ->assertCollectionHas($unemployedWrestler);
});

test('injured wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $injuredWrestlers = Wrestler::unemployed()->get();

    expect($injuredWrestlers)
        ->toHaveCount(1)
        ->assertCollectionHas($injuredWrestler);
});
