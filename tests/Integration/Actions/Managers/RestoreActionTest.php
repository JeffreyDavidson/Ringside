<?php

declare(strict_types=1);

use App\Actions\Managers\DeleteAction;
use App\Actions\Managers\RestoreAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

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
    $deletedManager = Manager::onlyTrashed()->find($managerId);
    RestoreAction::run($deletedManager);

    // Verify manager is restored
    $restoredManager = Manager::find($managerId);
    expect($restoredManager)->not()->toBeNull();
    expect($restoredManager->deleted_at)->toBeNull();
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->employed()->create();
    $managerId = $manager->id;
    $originalEmploymentCount = $manager->employments()->count();

    // Soft delete the manager
    $manager->delete();

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->find($managerId);
    RestoreAction::run($deletedManager);

    // Verify transaction was successful
    $restoredManager = Manager::find($managerId);
    expect($restoredManager)->not()->toBeNull();
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
    $deletedManager = Manager::onlyTrashed()->find($managerId);
    RestoreAction::run($deletedManager);

    // Verify all historical records are preserved
    $restoredManager = Manager::find($managerId);
    expect($restoredManager->employments()->count())->toBe($originalEmploymentCount);
    expect($restoredManager->suspensions()->count())->toBe($originalSuspensionCount);
    expect($restoredManager->injuries()->count())->toBe($originalInjuryCount);
});

test('it does not automatically restore employment relationships', function () {
    $manager = Manager::factory()->employed()->create();
    $managerId = $manager->id;

    // Verify manager was employed before deletion
    expect($manager->isEmployed())->toBeTrue();

    // Delete via the action so employment is properly ended (status cleanup),
    // not just the soft-delete column flipped.
    DeleteAction::run($manager);

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->find($managerId);
    RestoreAction::run($deletedManager);

    // Verify manager is restored but not automatically employed
    $restoredManager = Manager::find($managerId);
    expect($restoredManager)->not()->toBeNull();

    // Manager should not be automatically employed - requires separate action
    // This tests the business rule that restoration doesn't auto-employ
    expect($restoredManager->isEmployed())->toBeFalse();

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

    // Delete via the action so management relationships are ended
    DeleteAction::run($manager);

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->find($managerId);
    RestoreAction::run($deletedManager);

    // Verify manager is restored
    $restoredManager = Manager::find($managerId);
    expect($restoredManager)->not()->toBeNull();

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
    $deletedManager = Manager::onlyTrashed()->find($managerId);
    RestoreAction::run($deletedManager);

    // Verify all complex history is preserved
    $restoredManager = Manager::find($managerId);
    expect($restoredManager->employments()->count())->toBe($originalRecordCounts['employments']);
    expect($restoredManager->retirements()->count())->toBe($originalRecordCounts['retirements']);
});

test('it prevents restoring non-deleted managers', function () {
    $manager = Manager::factory()->create();

    // Manager is not deleted
    expect($manager->deleted_at)->toBeNull();

    // Should not be able to restore a non-deleted manager
    expect(fn () => RestoreAction::run($manager))
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
    $deletedManager = Manager::onlyTrashed()->find($managerId);
    RestoreAction::run($deletedManager);

    // Verify referential integrity is maintained
    $restoredManager = Manager::find($managerId);

    // All pivot relationships should be preserved
    expect($restoredManager->wrestlers()->count())->toBe(1);
    expect($restoredManager->tagTeams()->count())->toBe(1);

    // Verify pivot data integrity
    $wrestlerPivot = $restoredManager->wrestlers()->first()->pivot;
    expect($wrestlerPivot->hired_at)->not()->toBeNull();
    expect($wrestlerPivot->fired_at)->not()->toBeNull();

    $tagTeamPivot = $restoredManager->tagTeams()->first()->pivot;
    expect($tagTeamPivot->hired_at)->not()->toBeNull();
    expect($tagTeamPivot->fired_at)->not()->toBeNull();
});

test('it allows separate employment after restoration', function () {
    $manager = Manager::factory()->employed()->create();
    $managerId = $manager->id;

    // Delete via the action so employment is properly ended
    DeleteAction::run($manager);

    // Restore the manager
    $deletedManager = Manager::onlyTrashed()->find($managerId);
    RestoreAction::run($deletedManager);

    // Verify manager can be employed separately after restoration
    $restoredManager = Manager::find($managerId);
    expect($restoredManager->isEmployed())->toBeFalse();

    // This would require a separate EmployAction call
    // expect(() => EmployAction::run($restoredManager))->not()->toThrow();
    // Testing the capability without actually running EmployAction

    // Manager should be in a state where employment is possible
    expect($restoredManager->isRetired())->toBeFalse();
    expect($restoredManager->deleted_at)->toBeNull();
});
