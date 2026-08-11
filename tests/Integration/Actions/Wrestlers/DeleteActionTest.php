<?php

declare(strict_types=1);

use App\Actions\Wrestlers\DeleteAction;
use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it soft deletes an unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    expect($wrestler->isEmployed())->toBeFalse();
    expect($wrestler->trashed())->toBeFalse();

    resolve(DeleteAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Verify wrestler is soft deleted
    $this->assertSoftDeleted('wrestlers', [
        'id' => $wrestler->id,
        'name' => $wrestler->name,
    ]);
});

test('it soft deletes wrestler with specific deletion date', function () {
    $wrestler = Wrestler::factory()->create();
    $deletionDate = now()->subDays(2);

    resolve(DeleteAction::class)->handle($wrestler, $deletionDate);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Note: Laravel soft deletes use current timestamp, so we can't directly test custom dates
    // The custom date would be used for ending relationships, not the deleted_at timestamp
    $this->assertSoftDeleted('wrestlers', [
        'id' => $wrestler->id,
    ]);
});

test('it ends employment before deletion', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Get current employment to verify it gets ended
    $currentEmployment = $wrestler->currentEmployment()->firstOrFail();
    expect($currentEmployment->ended_at)->toBeNull();

    resolve(DeleteAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Verify employment was ended before deletion
    $this->assertDatabaseHas('wrestlers_employments', [
        'id' => $currentEmployment->id,
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends retirement before deletion', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    // Get current retirement to verify it gets ended
    $currentRetirement = $wrestler->currentRetirement()->firstOrFail();
    expect($currentRetirement->ended_at)->toBeNull();

    resolve(DeleteAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Verify retirement was ended before deletion
    $this->assertDatabaseHas('wrestlers_retirements', [
        'id' => $currentRetirement->id,
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends suspension before deletion', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    // Get current suspension to verify it gets ended
    $currentSuspension = $wrestler->currentSuspension()->firstOrFail();
    expect($currentSuspension->ended_at)->toBeNull();

    resolve(DeleteAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Verify suspension was ended before deletion
    $this->assertDatabaseHas('wrestlers_suspensions', [
        'id' => $currentSuspension->id,
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends injury before deletion', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    // Get current injury to verify it gets ended
    $currentInjury = $wrestler->currentInjury()->firstOrFail();
    expect($currentInjury->ended_at)->toBeNull();

    resolve(DeleteAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Verify injury was ended before deletion
    $this->assertDatabaseHas('wrestlers_injuries', [
        'id' => $currentInjury->id,
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it closes lifecycle periods and applies relationship cascades', function () {
    // Create employed wrestler with managers
    $wrestler = Wrestler::factory()->employed()->create();
    $manager = Manager::factory()->create();

    // Assign manager to wrestler
    $wrestler->managers()->attach($manager->id, [
        'hired_at' => now()->subDays(5),
        'fired_at' => null,
    ]);

    expect($wrestler->isEmployed())->toBeTrue();
    expect($wrestler->currentManagers)->toHaveCount(1);

    resolve(DeleteAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Verify employment period was ended
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify the current manager relationship ended
    $this->assertDatabaseHas('wrestlers_managers', [
        'wrestler_id' => $wrestler->id,
        'manager_id' => $manager->id,
        'fired_at' => now()->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    // Test with null date (should use now())
    resolve(DeleteAction::class)->handle($wrestler, null);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Verify employment ended with current timestamp
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it prevents deleting already deleted wrestler', function () {
    $wrestler = Wrestler::factory()->create();
    $wrestler->delete(); // Soft delete

    expect($wrestler->trashed())->toBeTrue();

    expect(fn () => resolve(DeleteAction::class)->handle($wrestler))
        ->toThrow(CannotBeDeletedException::class);
});

test('it maintains relationship history integrity', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $manager = Manager::factory()->create();

    // Create manager relationship history
    $wrestler->managers()->attach($manager->id, [
        'hired_at' => now()->subDays(30),
        'fired_at' => now()->subDays(20), // Already ended
    ]);

    $wrestler->managers()->attach($manager->id, [
        'hired_at' => now()->subDays(10),
        'fired_at' => null, // Current relationship
    ]);

    resolve(DeleteAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Old relationship should remain unchanged
    $this->assertDatabaseHas('wrestlers_managers', [
        'wrestler_id' => $wrestler->id,
        'manager_id' => $manager->id,
        'hired_at' => now()->subDays(30)->toDateTimeString(),
        'fired_at' => now()->subDays(20)->toDateTimeString(),
    ]);

    // Current relationship should be ended
    $this->assertDatabaseHas('wrestlers_managers', [
        'wrestler_id' => $wrestler->id,
        'manager_id' => $manager->id,
        'hired_at' => now()->subDays(10)->toDateTimeString(),
        'fired_at' => now()->toDateTimeString(),
    ]);
});

test('it handles wrestler with no active relationships', function () {
    $wrestler = Wrestler::factory()->create();

    // Create only historical relationships (already ended)
    $wrestler->employments()->create([
        'started_at' => now()->subDays(60),
        'ended_at' => now()->subDays(30),
    ]);

    $wrestler->retirements()->create([
        'started_at' => now()->subDays(100),
        'ended_at' => now()->subDays(80),
    ]);

    expect($wrestler->isEmployed())->toBeFalse();
    expect($wrestler->isRetired())->toBeFalse();

    resolve(DeleteAction::class)->handle($wrestler);

    $wrestler->refresh();
    expect($wrestler->trashed())->toBeTrue();

    // Historical relationships should remain unchanged
    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->subDays(30)->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => now()->subDays(80)->toDateTimeString(),
    ]);
});
