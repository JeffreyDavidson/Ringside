<?php

declare(strict_types=1);

use App\Actions\Managers\RetireAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires an employed manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isRetired())->toBeFalse();

    resolve(RetireAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isRetired())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();

    // Verify retirement record was created
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Verify employment was ended
    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it retires manager with specific retirement date', function () {
    $manager = Manager::factory()->employed()->create();
    $retirementDate = now()->subDays(5);

    resolve(RetireAction::class)->handle($manager, $retirementDate);

    $manager->refresh();
    expect($manager->isRetired())->toBeTrue();

    // Verify retirement and employment ended with specific date
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => $retirementDate->toDateTimeString(),
    ]);
});

test('it retires suspended manager and ends suspension', function () {
    $manager = Manager::factory()->suspended()->create();

    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    resolve(RetireAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isRetired())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();
    expect($manager->isEmployed())->toBeFalse();

    // Verify suspension was ended
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify retirement was created
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it retires injured manager and ends injury', function () {
    $manager = Manager::factory()->injured()->create();

    expect($manager->isInjured())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    resolve(RetireAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isRetired())->toBeTrue();
    expect($manager->isInjured())->toBeFalse();
    expect($manager->isEmployed())->toBeFalse();

    // Verify injury was ended
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify retirement was created
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it ends current management relationships', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->create();

    // Assign manager to wrestler and tag team
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDays(30)]);
    $manager->tagTeams()->attach($tagTeam->id, ['hired_at' => now()->subDays(20)]);

    expect($manager->currentWrestlers)->toHaveCount(1);
    expect($manager->currentTagTeams)->toHaveCount(1);

    resolve(RetireAction::class)->handle($manager);

    $manager->refresh();

    expect($manager->currentWrestlers)->toHaveCount(0);
    expect($manager->currentTagTeams)->toHaveCount(0);

    // Verify relationships were ended with retirement date
    $this->assertDatabaseHas('wrestlers_managers', [
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'fired_at' => now()->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('tag_teams_managers', [
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'fired_at' => now()->toDateTimeString(),
    ]);
});

test('it persists retirement and ends current relationships', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    // Set up management relationship
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDay()]);

    expect($manager->currentRetirement)->toBeNull();
    expect($manager->currentWrestlers)->toHaveCount(1);

    resolve(RetireAction::class)->handle($manager);

    $manager->refresh();

    // Verify retirement period was created
    expect($manager->currentRetirement)->not()->toBeNull();
    expect($manager->isRetired())->toBeTrue();

    expect($manager->currentWrestlers)->toHaveCount(0);
});

test('it prevents retiring already retired manager', function () {
    $manager = Manager::factory()->retired()->create();

    expect($manager->isRetired())->toBeTrue();

    expect(fn () => resolve(RetireAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it prevents retiring unemployed manager', function () {
    $manager = Manager::factory()->create();

    expect($manager->isEmployed())->toBeFalse();

    expect(fn () => resolve(RetireAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDay()]);

    resolve(RetireAction::class)->handle($manager);

    $manager->refresh();

    // Verify transaction was successful - all operations completed
    expect($manager->isRetired())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();
    expect($manager->currentWrestlers)->toHaveCount(0);

    // Verify all database changes are consistent
    $retirement = $manager->currentRetirement()->firstOrFail();
    expect(requiredDate($retirement->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($retirement->ended_at)->toBeNull();
});

test('it uses the provided date', function () {
    $manager = Manager::factory()->employed()->create();
    $customRetirementDate = now()->subDays(3)->startOfDay();

    resolve(RetireAction::class)->handle($manager, $customRetirementDate);

    $manager->refresh();

    // Verify the provided date was used across all operations
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'started_at' => $customRetirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => $customRetirementDate->toDateTimeString(),
    ]);
});

test('it preserves management history during retirement', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    // Create historical and current management relationships
    $manager->wrestlers()->attach($wrestler->id, [
        'hired_at' => now()->subDays(30),
        'fired_at' => now()->subDays(20), // Historical relationship
    ]);
    $manager->wrestlers()->attach($wrestler->id, [
        'hired_at' => now()->subDays(10), // Current relationship
    ]);

    expect($manager->wrestlers()->count())->toBe(2); // Total relationships
    expect($manager->currentWrestlers)->toHaveCount(1); // Current relationships

    resolve(RetireAction::class)->handle($manager);

    $manager->refresh();

    // Should preserve all historical relationships while ending current ones
    expect($manager->wrestlers()->count())->toBe(2); // Historical preserved
    expect($manager->currentWrestlers)->toHaveCount(0); // Current ended

    // Verify the current relationship was ended with retirement date
    $currentRelationship = WrestlerManager::query()
        ->whereBelongsTo($manager)
        ->where('hired_at', now()->subDays(10))
        ->firstOrFail();

    expect(requiredDate($currentRelationship->fired_at)->toDateTimeString())->toBe(now()->toDateTimeString());
});
