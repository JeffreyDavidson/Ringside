<?php

declare(strict_types=1);

use App\Actions\Managers\ReleaseAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it releases an employed manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->currentEmployment()->exists())->toBeTrue();
    expect($manager->isReleased())->toBeFalse();

    resolve(ReleaseAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isReleased())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeFalse();

    // Verify employment was ended
    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it releases manager with specific release date', function () {
    $manager = Manager::factory()->employed()->create();
    $releaseDate = now()->subDays(4);

    resolve(ReleaseAction::class)->handle($manager, $releaseDate);

    $manager->refresh();
    expect($manager->isReleased())->toBeTrue();

    // Verify employment ended with specific date
    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => $releaseDate->toDateTimeString(),
    ]);
});

test('it releases suspended manager and ends suspension', function () {
    $manager = Manager::factory()->suspended()->create();

    expect($manager->currentSuspension()->exists())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeTrue();

    resolve(ReleaseAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isReleased())->toBeTrue();
    expect($manager->currentSuspension()->exists())->toBeFalse();
    expect($manager->currentEmployment()->exists())->toBeFalse();

    // Verify suspension was ended
    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify employment was ended
    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it releases injured manager and ends injury', function () {
    $manager = Manager::factory()->injured()->create();

    expect($manager->currentInjury()->exists())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeTrue();

    resolve(ReleaseAction::class)->handle($manager);

    $manager->refresh();
    expect($manager->isReleased())->toBeTrue();
    expect($manager->currentInjury()->exists())->toBeFalse();
    expect($manager->currentEmployment()->exists())->toBeFalse();

    // Verify injury was ended
    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $manager->id,
        'injurable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify employment was ended
    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
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

    resolve(ReleaseAction::class)->handle($manager);

    $manager->refresh();

    expect($manager->currentWrestlers)->toHaveCount(0);
    expect($manager->currentTagTeams)->toHaveCount(0);

    // Verify relationships were ended with release date
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

test('it persists release and ends current relationships', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    // Set up management relationship
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDay()]);

    expect($manager->isReleased())->toBeFalse();
    expect($manager->currentWrestlers)->toHaveCount(1);

    resolve(ReleaseAction::class)->handle($manager);

    $manager->refresh();

    // Verify the release lifecycle state
    expect($manager->isReleased())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeFalse();

    expect($manager->currentWrestlers)->toHaveCount(0);
});

test('it prevents releasing already released manager', function () {
    $manager = Manager::factory()->released()->create();

    expect($manager->isReleased())->toBeTrue();

    expect(fn () => resolve(ReleaseAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it prevents releasing unemployed manager', function () {
    $manager = Manager::factory()->create();

    expect($manager->currentEmployment()->exists())->toBeFalse();

    expect(fn () => resolve(ReleaseAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->suspended()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDay()]);

    resolve(ReleaseAction::class)->handle($manager);

    $manager->refresh();

    // Verify transaction was successful - all operations completed
    expect($manager->isReleased())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeFalse();
    expect($manager->currentSuspension()->exists())->toBeFalse();
    expect($manager->currentWrestlers)->toHaveCount(0);

    // Verify all database changes are consistent
    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it uses the provided date', function () {
    $manager = Manager::factory()->suspended()->create();
    $customReleaseDate = now()->subDays(2)->startOfDay();

    resolve(ReleaseAction::class)->handle($manager, $customReleaseDate);

    $manager->refresh();

    // Verify the provided date was used across all operations
    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => $customReleaseDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'ended_at' => $customReleaseDate->toDateTimeString(),
    ]);
});

test('it preserves management history during release', function () {
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

    resolve(ReleaseAction::class)->handle($manager);

    $manager->refresh();

    // Should preserve all historical relationships while ending current ones
    expect($manager->wrestlers()->count())->toBe(2); // Historical preserved
    expect($manager->currentWrestlers)->toHaveCount(0); // Current ended

    // Verify the current relationship was ended with release date
    $currentRelationship = WrestlerManager::query()
        ->whereBelongsTo($manager)
        ->where('hired_at', now()->subDays(10))
        ->firstOrFail();

    expect(requiredDate($currentRelationship->fired_at)->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it handles manager with no management relationships', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->currentWrestlers)->toHaveCount(0);
    expect($manager->currentTagTeams)->toHaveCount(0);

    resolve(ReleaseAction::class)->handle($manager);

    $manager->refresh();

    // Should release successfully even without relationships
    expect($manager->isReleased())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeFalse();

    $this->assertDatabaseHas('employments', [
        'employable_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it handles complex status combinations', function () {
    $manager = Manager::factory()->employed()->create();

    // Manually set up a complex status (shouldn't be possible normally)
    $manager->suspensions()->create(['started_at' => now()->subDays(5), 'ended_at' => now()->subDays(3)]);
    $manager->suspensions()->create(['started_at' => now()->subDays(2), 'ended_at' => null]); // Current

    $manager->refresh();
    expect($manager->currentSuspension()->exists())->toBeTrue();
    expect($manager->currentEmployment()->exists())->toBeTrue();

    resolve(ReleaseAction::class)->handle($manager);

    $manager->refresh();

    // Should handle complex status properly
    expect($manager->isReleased())->toBeTrue();
    expect($manager->currentSuspension()->exists())->toBeFalse();
    expect($manager->currentEmployment()->exists())->toBeFalse();

    // Should end only the current suspension
    expect($manager->suspensions()->whereNull('ended_at')->count())->toBe(0);
    expect($manager->suspensions()->count())->toBe(2); // Preserve historical
});
