<?php

declare(strict_types=1);

use App\Actions\Referees\InjureAction;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Roster\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it injures an employed referee', function () {
    $referee = Referee::factory()->employed()->create();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isInjured())->toBeFalse();

    resolve(InjureAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isInjured())->toBeTrue();

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $referee->id,
        'injurable_type' => $referee->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it injures referee with specific injury date', function () {
    $referee = Referee::factory()->employed()->create();
    $injuryDate = now()->subDays(3);

    resolve(InjureAction::class)->handle($referee, $injuryDate);

    $referee->refresh();
    expect($referee->isInjured())->toBeTrue();

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $referee->id,
        'injurable_type' => $referee->getMorphClass(),
        'started_at' => $injuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses the provided date', function () {
    $referee = Referee::factory()->employed()->create();
    $injuryDate = now()->subDays(7);

    resolve(InjureAction::class)->handle($referee, $injuryDate);

    $referee->refresh();

    // The provided injury date should be persisted
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $referee->id,
        'injurable_type' => $referee->getMorphClass(),
        'started_at' => $injuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it validates referee can be injured', function () {
    $referee = Referee::factory()->employed()->create();

    // Should succeed without throwing validation exception
    resolve(InjureAction::class)->handle($referee);

    $referee->refresh();
    expect($referee->isInjured())->toBeTrue();
});

test('it throws exception when referee cannot be injured', function () {
    $referee = Referee::factory()->create(); // Not employed

    expect($referee->isEmployed())->toBeFalse();

    expect(fn () => resolve(InjureAction::class)->handle($referee))
        ->toThrow(CannotBeInjuredException::class);
});

test('it prevents injuring a suspended referee', function () {
    $referee = Referee::factory()->suspended()->create();

    expect($referee->isEmployed())->toBeTrue();

    expect(fn () => resolve(InjureAction::class)->handle($referee))
        ->toThrow(CannotBeInjuredException::class);

    $referee->refresh();

    expect($referee->isSuspended())->toBeTrue()
        ->and($referee->isInjured())->toBeFalse()
        ->and($referee->isEmployed())->toBeTrue();
});

test('it maintains transaction boundaries', function () {
    $referee = Referee::factory()->employed()->create();

    resolve(InjureAction::class)->handle($referee);

    $referee->refresh();

    // Injury creation should be atomic
    expect($referee->isInjured())->toBeTrue();

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $referee->id,
        'injurable_type' => $referee->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it maintains referee employment after injury', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment()->firstOrFail();

    expect($referee->isEmployed())->toBeTrue();

    resolve(InjureAction::class)->handle($referee);

    $referee->refresh();
    $employment->refresh();

    // Should remain employed after injury
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isInjured())->toBeTrue();
    expect($employment->ended_at)->toBeNull();
});

test('it creates injury record with correct structure', function () {
    $referee = Referee::factory()->employed()->create();
    $injuryDate = now()->subDays(2);

    resolve(InjureAction::class)->handle($referee, $injuryDate);

    $injury = freshModel($referee)->currentInjury()->firstOrFail();

    expect($injury)->not->toBeNull();
    expect($injury->injurable_id)->toBe($referee->id)
        ->and($injury->injurable_type)->toBe($referee->getMorphClass());
    expect(requiredDate($injury->started_at)->toDateTimeString())->toBe($injuryDate->toDateTimeString());
    expect($injury->ended_at)->toBeNull();
});
