<?php

declare(strict_types=1);

use App\Actions\Managers\AssignManagersAction;
use App\Actions\Managers\EndManagerAssignmentsForManagerAction;
use App\Actions\Managers\SynchronizeManagerAssignmentsAction;
use App\Exceptions\Roster\Managers\CannotBeAssignedException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\assertDatabaseHas;

it('assigns employed managers to a wrestler without changing their employment', function () {
    $wrestler = Wrestler::factory()->create();
    $managers = Manager::factory()->employed()->count(2)->create();
    $assignmentDate = now()->subDay();

    resolve(AssignManagersAction::class)->handle($wrestler, $managers, $assignmentDate);

    expect($wrestler->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing($managers->modelKeys())
        ->and($managers->every(fn (Manager $manager): bool => $manager->currentEmployment()->exists()))
        ->toBeTrue();

    foreach ($managers as $manager) {
        assertDatabaseHas('wrestlers_managers', [
            'wrestler_id' => $wrestler->id,
            'manager_id' => $manager->id,
            'hired_at' => $assignmentDate->toDateTimeString(),
            'fired_at' => null,
        ]);
    }
});

it('assigns managers to a tag team through the same boundary', function () {
    $tagTeam = TagTeam::factory()->create();
    $manager = Manager::factory()->employed()->create();
    $assignmentDate = now()->subDay();

    resolve(AssignManagersAction::class)->handle(
        $tagTeam,
        new Collection([$manager]),
        $assignmentDate,
    );

    expect($tagTeam->currentManagers()->whereKey($manager->id)->exists())->toBeTrue();

    assertDatabaseHas('tag_teams_managers', [
        'tag_team_id' => $tagTeam->id,
        'manager_id' => $manager->id,
        'hired_at' => $assignmentDate->toDateTimeString(),
        'fired_at' => null,
    ]);
});

it('synchronizes current managers while preserving relationship history', function () {
    $wrestler = Wrestler::factory()->create();
    $retainedManager = Manager::factory()->employed()->create();
    $removedManager = Manager::factory()->employed()->create();
    $addedManager = Manager::factory()->employed()->create();
    $assignManagers = resolve(AssignManagersAction::class);
    $synchronizeAssignments = resolve(SynchronizeManagerAssignmentsAction::class);
    $assignManagers->handle(
        $wrestler,
        new Collection([$retainedManager, $removedManager]),
        now()->subDay(),
    );
    $changeDate = now();

    $synchronizeAssignments->handle(
        $wrestler,
        new Collection([$retainedManager, $addedManager]),
        $changeDate,
    );

    expect($wrestler->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing([$retainedManager->id, $addedManager->id])
        ->and($wrestler->previousManagers()->whereKey($removedManager->id)->exists())
        ->toBeTrue();

    assertDatabaseHas('wrestlers_managers', [
        'wrestler_id' => $wrestler->id,
        'manager_id' => $removedManager->id,
        'fired_at' => $changeDate->toDateTimeString(),
    ]);
});

it('leaves manager assignments unchanged when synchronization is omitted', function () {
    $tagTeam = TagTeam::factory()->create();
    $manager = Manager::factory()->employed()->create();
    $assignManagers = resolve(AssignManagersAction::class);
    $synchronizeAssignments = resolve(SynchronizeManagerAssignmentsAction::class);
    $assignManagers->handle($tagTeam, new Collection([$manager]), now()->subDay());

    $synchronizeAssignments->handle($tagTeam, null, now());

    expect($tagTeam->currentManagers()->whereKey($manager->id)->exists())->toBeTrue();
});

it('accepts an empty manager collection', function () {
    $wrestler = Wrestler::factory()->create();

    resolve(AssignManagersAction::class)->handle(
        $wrestler,
        new Collection,
        now(),
    );

    expect($wrestler->managers()->exists())->toBeFalse();
});

it('refuses a manager who is not available and attaches none of the batch', function (string $state, string $message) {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $eligibleManager = Manager::factory()->employed()->create();
    $unavailableManager = Manager::factory()->{$state}()->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);
    $managers = Manager::query()->whereKey([$eligibleManager->id, $unavailableManager->id])->get();

    // Act
    $assign = fn () => resolve(AssignManagersAction::class)->handle($wrestler, $managers, now());

    // Assert
    expect($assign)->toThrow(CannotBeAssignedException::class, $message)
        ->and($wrestler->managers()->exists())->toBeFalse();
})->with([
    'never employed' => ['unemployed', 'Manager Bobby Heenan is not currently employed and cannot be assigned.'],
    'released' => ['released', 'Manager Bobby Heenan is not currently employed and cannot be assigned.'],
    'retired' => ['retired', 'Manager Bobby Heenan is retired and cannot be assigned.'],
    'suspended' => ['suspended', 'Manager Bobby Heenan is suspended and cannot be assigned.'],
    'injured' => ['injured', 'Manager Bobby Heenan is injured and cannot be assigned.'],
]);

it('locks the incoming managers in ascending id order before attaching them', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    Manager::factory()->employed()->count(2)->create();
    $managers = Manager::query()->latest('id')->get();

    // Act
    $statements = recordStatements(fn () => resolve(AssignManagersAction::class)->handle($wrestler, $managers, now()));

    // Assert
    $lockPosition = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "managers"'));
    $attachPosition = statementPosition($statements, fn (array $statement): bool => str_contains($statement['sql'], 'insert into "wrestlers_managers"'));

    expect($statements[$lockPosition]['sql'])->toContain('order by "managers"."id" asc')
        ->and($lockPosition)->toBeLessThan($attachPosition);
});

it('refuses a manager who was deleted after the form validated and attaches nothing', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->employed()->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);
    $managers = Manager::query()->whereKey($manager->id)->get();
    $manager->delete();

    // Act
    $assign = fn () => resolve(AssignManagersAction::class)->handle($wrestler, $managers, now());

    // Assert
    expect($assign)->toThrow(CannotBeAssignedException::class, 'Manager Bobby Heenan has been deleted and cannot be assigned.')
        ->and($wrestler->managers()->exists())->toBeFalse();
});

it('refuses a newly added manager who is not available when synchronizing', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $retainedManager = Manager::factory()->employed()->create();
    $releasedManager = Manager::factory()->released()->create();
    resolve(AssignManagersAction::class)->handle($tagTeam, new Collection([$retainedManager]), now()->subDay());

    // Act
    $synchronize = fn () => resolve(SynchronizeManagerAssignmentsAction::class)->handle(
        $tagTeam,
        new Collection([$retainedManager, $releasedManager]),
        now(),
    );

    // Assert
    expect($synchronize)->toThrow(CannotBeAssignedException::class)
        ->and($tagTeam->currentManagers()->pluck('managers.id')->all())->toBe([$retainedManager->id]);
});

it('keeps a manager who was assigned and is no longer available when synchronizing', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $assignedManager = Manager::factory()->suspended()->create();
    $addedManager = Manager::factory()->employed()->create();
    $wrestler->managers()->attach($assignedManager, ['hired_at' => now()->subMonth()]);

    // Act
    resolve(SynchronizeManagerAssignmentsAction::class)->handle(
        $wrestler,
        new Collection([$assignedManager, $addedManager]),
        now(),
    );

    // Assert
    expect($wrestler->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing([$assignedManager->id, $addedManager->id]);
});

it('preserves each manager assignment when a manager is reassigned', function () {
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->employed()->create();
    $managers = new Collection([$manager]);
    $noManagers = new Collection;
    $firstHiredAt = now()->subDays(4)->startOfSecond();
    $firstFiredAt = now()->subDays(3)->startOfSecond();
    $secondHiredAt = now()->subDays(2)->startOfSecond();
    $secondFiredAt = now()->subDay()->startOfSecond();
    $assignManagers = resolve(AssignManagersAction::class);
    $synchronizeAssignments = resolve(SynchronizeManagerAssignmentsAction::class);

    $assignManagers->handle($wrestler, $managers, $firstHiredAt);
    $synchronizeAssignments->handle($wrestler, $noManagers, $firstFiredAt);
    $assignManagers->handle($wrestler, $managers, $secondHiredAt);
    $synchronizeAssignments->handle($wrestler, $noManagers, $secondFiredAt);

    $assignments = WrestlerManager::query()
        ->whereBelongsTo($wrestler)
        ->whereBelongsTo($manager)
        ->orderBy('hired_at')
        ->get();
    $firstAssignment = $assignments->firstOrFail();
    $secondAssignment = $assignments->skip(1)->firstOrFail();

    expect($assignments)->toHaveCount(2)
        ->and($firstAssignment->hired_at->equalTo($firstHiredAt))->toBeTrue()
        ->and($firstAssignment->fired_at?->equalTo($firstFiredAt))->toBeTrue()
        ->and($secondAssignment->hired_at->equalTo($secondHiredAt))->toBeTrue()
        ->and($secondAssignment->fired_at?->equalTo($secondFiredAt))->toBeTrue()
        ->and($wrestler->currentManagers()->exists())->toBeFalse();
});

it("ends only a manager's current wrestler and tag team assignments", function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $historicalEnd = now()->subDays(2)->startOfSecond();
    $assignmentEnd = now()->startOfSecond();

    $manager->wrestlers()->attach($wrestler, [
        'hired_at' => now()->subDays(4),
        'fired_at' => $historicalEnd,
    ]);
    $manager->wrestlers()->attach($wrestler, [
        'hired_at' => now()->subDay(),
        'fired_at' => null,
    ]);
    $manager->tagTeams()->attach($tagTeam, [
        'hired_at' => now()->subDays(4),
        'fired_at' => $historicalEnd,
    ]);
    $manager->tagTeams()->attach($tagTeam, [
        'hired_at' => now()->subDay(),
        'fired_at' => null,
    ]);

    resolve(EndManagerAssignmentsForManagerAction::class)->handle($manager, $assignmentEnd);

    $wrestlerAssignments = WrestlerManager::query()
        ->whereBelongsTo($manager)
        ->whereBelongsTo($wrestler)
        ->oldest('hired_at')
        ->get();
    $tagTeamAssignments = TagTeamManager::query()
        ->whereBelongsTo($manager)
        ->whereBelongsTo($tagTeam, 'tagTeam')
        ->oldest('hired_at')
        ->get();
    $historicalWrestlerAssignment = $wrestlerAssignments->firstOrFail();
    $endedWrestlerAssignment = $wrestlerAssignments->skip(1)->firstOrFail();
    $historicalTagTeamAssignment = $tagTeamAssignments->firstOrFail();
    $endedTagTeamAssignment = $tagTeamAssignments->skip(1)->firstOrFail();

    expect($historicalWrestlerAssignment->fired_at?->equalTo($historicalEnd))->toBeTrue()
        ->and($endedWrestlerAssignment->fired_at?->equalTo($assignmentEnd))->toBeTrue()
        ->and($historicalTagTeamAssignment->fired_at?->equalTo($historicalEnd))->toBeTrue()
        ->and($endedTagTeamAssignment->fired_at?->equalTo($assignmentEnd))->toBeTrue()
        ->and($manager->currentWrestlers()->exists())->toBeFalse()
        ->and($manager->currentTagTeams()->exists())->toBeFalse();
});
