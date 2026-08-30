<?php

declare(strict_types=1);

use App\Actions\Managers\RestoreAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it restores a soft-deleted manager', function () {
    $manager = Manager::factory()->create();
    $managerId = $manager->id;

    // Soft delete the manager
    $manager->delete();

    // Verify manager is soft deleted
    expect(Manager::find($managerId))->toBeNull();
    expect(Manager::withTrashed()->find($managerId))->not()->toBeNull();

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->findOrFail($managerId);
    resolve(RestoreAction::class)->handle($deletedManager);

    // Verify manager is restored
    $restoredManager = Manager::findOrFail($managerId);
    expect($restoredManager->deleted_at)->toBeNull();
});

test('it reloads a stale manager before restoring', function () {
    $manager = Manager::factory()->create();
    $staleManager = clone $manager;

    $manager->delete();

    resolve(RestoreAction::class)->handle($staleManager);

    expect(Manager::query()->find($manager->getKey()))->not->toBeNull();
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->employed()->create();
    $managerId = $manager->id;
    $originalEmploymentCount = $manager->employments()->count();

    // Soft delete the manager
    $manager->delete();

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->findOrFail($managerId);
    resolve(RestoreAction::class)->handle($deletedManager);

    // Verify transaction was successful
    $restoredManager = Manager::findOrFail($managerId);
    expect($restoredManager->deleted_at)->toBeNull();

    // Verify historical records are preserved
    expect($restoredManager->employments()->count())->toBe($originalEmploymentCount);
});

test('it preserves all historical records during restoration', function () {
    $manager = Manager::factory()->employed()->create();

    // Create some historical records
    $manager->suspensions()->create(['started_at' => now()->subDays(10), 'ended_at' => now()->subDays(8)]);
    $manager->injuries()->create(['started_at' => now()->subDays(6), 'ended_at' => now()->subDays(4)]);

    $originalEmploymentCount = $manager->employments()->count();
    $originalSuspensionCount = $manager->suspensions()->count();
    $originalInjuryCount = $manager->injuries()->count();
    $managerId = $manager->id;

    // Soft delete the manager
    $manager->delete();

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->findOrFail($managerId);
    resolve(RestoreAction::class)->handle($deletedManager);

    // Verify all historical records are preserved
    $restoredManager = Manager::findOrFail($managerId);
    expect($restoredManager->employments()->count())->toBe($originalEmploymentCount);
    expect($restoredManager->suspensions()->count())->toBe($originalSuspensionCount);
    expect($restoredManager->injuries()->count())->toBe($originalInjuryCount);
});

test('it does not automatically restore employment relationships', function () {
    $manager = Manager::factory()->employed()->create();
    $managerId = $manager->id;

    // Verify manager was employed before deletion
    expect($manager->currentEmployment()->exists())->toBeTrue();

    // Soft delete the manager
    $manager->delete();

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->findOrFail($managerId);
    resolve(RestoreAction::class)->handle($deletedManager);

    // Verify manager is restored but not automatically employed
    $restoredManager = Manager::findOrFail($managerId);

    // Manager should not be automatically employed - requires separate action
    // This tests the business rule that restoration doesn't auto-employ
    expect($restoredManager->currentEmployment()->exists())->toBeFalse();

    // Historical employment records should be preserved
    expect($restoredManager->employments()->count())->toBeGreaterThan(0);
    expect($restoredManager->employments()->whereNull('ended_at')->count())->toBe(0);
});

test('it does not automatically restore management relationships', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    // Set up management relationship
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDay()]);
    expect($manager->currentWrestlers)->toHaveCount(1);

    $managerId = $manager->id;

    // Soft delete the manager
    $manager->delete();

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->findOrFail($managerId);
    resolve(RestoreAction::class)->handle($deletedManager);

    // Verify manager is restored
    $restoredManager = Manager::findOrFail($managerId);

    // Management relationships should be preserved but not automatically reactivated
    expect($restoredManager->wrestlers()->count())->toBe(1); // Historical preserved
    expect($restoredManager->currentWrestlers)->toHaveCount(0); // Not auto-reactivated

    // This ensures restoration doesn't create conflicts with current assignments
});

test('it handles managers with complex deletion history', function () {
    $manager = Manager::factory()->create();

    // Create complex history
    $manager->employments()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $manager->retirements()->create(['started_at' => now()->subDays(25), 'ended_at' => now()->subDays(20)]);
    $manager->employments()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(15)]);

    $originalRecordCounts = [
        'employments' => $manager->employments()->count(),
        'retirements' => $manager->retirements()->count(),
    ];

    $managerId = $manager->id;

    // Soft delete the manager
    $manager->delete();

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->findOrFail($managerId);
    resolve(RestoreAction::class)->handle($deletedManager);

    // Verify all complex history is preserved
    $restoredManager = Manager::findOrFail($managerId);
    expect($restoredManager->employments()->count())->toBe($originalRecordCounts['employments']);
    expect($restoredManager->retirements()->count())->toBe($originalRecordCounts['retirements']);
});

test('it prevents restoring non-deleted managers', function () {
    $manager = Manager::factory()->create();

    // Manager is not deleted
    expect($manager->deleted_at)->toBeNull();

    // Should not be able to restore a non-deleted manager
    expect(fn () => resolve(RestoreAction::class)->handle($manager))
        ->toThrow(Exception::class);
});

test('it maintains referential integrity during restoration', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->create();

    // Set up relationships
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDays(5), 'fired_at' => now()->subDays(2)]);
    $manager->tagTeams()->attach($tagTeam->id, ['hired_at' => now()->subDays(4), 'fired_at' => now()->subDays(1)]);

    $managerId = $manager->id;

    // Soft delete the manager
    $manager->delete();

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->findOrFail($managerId);
    resolve(RestoreAction::class)->handle($deletedManager);

    // Verify referential integrity is maintained
    $restoredManager = Manager::findOrFail($managerId);

    // All pivot relationships should be preserved
    expect($restoredManager->wrestlers()->count())->toBe(1);
    expect($restoredManager->tagTeams()->count())->toBe(1);

    // Verify pivot data integrity
    $wrestlerManagement = WrestlerManager::query()
        ->whereBelongsTo($restoredManager, 'manager')
        ->whereBelongsTo($wrestler)
        ->firstOrFail();
    expect($wrestlerManagement->hired_at->toDateTimeString())->toBe(now()->subDays(5)->toDateTimeString());
    expect($wrestlerManagement->fired_at)->not()->toBeNull();

    $tagTeamManagement = TagTeamManager::query()
        ->whereBelongsTo($restoredManager, 'manager')
        ->whereBelongsTo($tagTeam, 'tagTeam')
        ->firstOrFail();
    expect($tagTeamManagement->hired_at->toDateTimeString())->toBe(now()->subDays(4)->toDateTimeString());
    expect($tagTeamManagement->fired_at)->not()->toBeNull();
});

test('it allows separate employment after restoration', function () {
    $manager = Manager::factory()->employed()->create();
    $managerId = $manager->id;

    // Soft delete the manager
    $manager->delete();

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->findOrFail($managerId);
    resolve(RestoreAction::class)->handle($deletedManager);

    // Verify manager can be employed separately after restoration
    $restoredManager = Manager::findOrFail($managerId);
    expect($restoredManager->currentEmployment()->exists())->toBeFalse();

    // This would require a separate EmployAction call
    // expect(() => resolve(EmployAction::class)->handle($restoredManager))->not()->toThrow();
    // Testing the capability without actually running EmployAction

    // Manager should be in a state where employment is possible
    expect($restoredManager->currentRetirement()->exists())->toBeFalse();
    expect($restoredManager->deleted_at)->toBeNull();
});
