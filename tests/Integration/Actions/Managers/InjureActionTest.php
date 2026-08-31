<?php

declare(strict_types=1);

use App\Actions\Managers\InjureAction;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Roster\Managers\Manager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it injures an employed manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->currentEmployment()->exists())->toBeTrue();
    expect($manager->currentInjury()->exists())->toBeFalse();

    resolve(InjureAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->currentInjury()->exists())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeTrue(); // Should remain employed while injured

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it injures manager with specific injury date', function () {
    $manager = Manager::factory()->employed()->create();
    $injuryDate = now()->subDays(4);

    resolve(InjureAction::class)->handle($manager, $injuryDate);

    $manager->refresh();
    expect($manager->currentInjury()->exists())->toBeTrue();

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'started_at' => $injuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it persists the injury lifecycle', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->currentInjury)->toBeNull();

    resolve(InjureAction::class)->handle($manager);

    $manager->refresh();

    // Verify injury period was created
    expect($manager->currentInjury)->not()->toBeNull();
    expect($manager->currentInjury()->exists())->toBeTrue();

    // Verify injury record shows proper start date
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents injuring already injured manager', function () {
    $manager = Manager::factory()->injured()->create();

    expect($manager->currentInjury()->exists())->toBeTrue();

    expect(fn () => resolve(InjureAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it prevents injuring unemployed manager', function () {
    $manager = Manager::factory()->create();

    expect($manager->currentEmployment()->exists())->toBeFalse();

    expect(fn () => resolve(InjureAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->employed()->create();

    resolve(InjureAction::class)->handle($manager);

    $manager->refresh();

    // Verify the transaction was successful
    expect($manager->currentInjury()->exists())->toBeTrue();

    // Verify injury record integrity
    $injury = $manager->currentInjury()->firstOrFail();
    expect(requiredDate($injury->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($injury->ended_at)->toBeNull();
});

test('it maintains employment status during injury', function () {
    $manager = Manager::factory()->employed()->create();
    $employmentId = $manager->currentEmployment()->firstOrFail()->id;

    expect($manager->currentEmployment()->exists())->toBeTrue();
    expect($manager->currentInjury()->exists())->toBeFalse();

    resolve(InjureAction::class)->handle($manager);

    $manager->refresh();

    // Should maintain employment while adding injury
    expect($manager->currentEmployment()->exists())->toBeTrue();
    expect($manager->currentInjury()->exists())->toBeTrue();

    // Employment record should remain unchanged
    $employment = $manager->currentEmployment()->firstOrFail();
    expect($employment->id)->toBe($employmentId);
    expect($employment->ended_at)->toBeNull();
});

test('it prevents injuring a suspended manager', function () {
    $manager = Manager::factory()->suspended()->create();

    expect($manager->currentSuspension()->exists())->toBeTrue();
    expect($manager->currentInjury()->exists())->toBeFalse();

    expect(fn () => resolve(InjureAction::class)->handle($manager))
        ->toThrow(CannotBeInjuredException::class);

    $manager->refresh();

    expect($manager->currentSuspension()->exists())->toBeTrue();
    expect($manager->currentInjury()->exists())->toBeFalse();
    expect($manager->currentEmployment()->exists())->toBeTrue();
});

test('it uses the provided date', function () {
    $manager = Manager::factory()->employed()->create();
    $customInjuryDate = now()->subDays(2)->startOfDay();

    resolve(InjureAction::class)->handle($manager, $customInjuryDate);

    $manager->refresh();

    // Verify the provided date was persisted
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'started_at' => $customInjuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});
