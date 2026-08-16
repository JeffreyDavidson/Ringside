<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use App\Services\ManagerAssignmentService;
use Illuminate\Database\Eloquent\Collection;

it('assigns managers to a wrestler without employing them', function () {
    $wrestler = Wrestler::factory()->create();
    $managers = Manager::factory()->count(2)->create();
    $assignmentDate = now()->subDay();

    resolve(ManagerAssignmentService::class)->assign($wrestler, $managers, $assignmentDate);

    expect($wrestler->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing($managers->modelKeys())
        ->and($managers->every(fn (Manager $manager): bool => ! $manager->isEmployed()))
        ->toBeTrue();

    foreach ($managers as $manager) {
        $this->assertDatabaseHas('wrestlers_managers', [
            'wrestler_id' => $wrestler->id,
            'manager_id' => $manager->id,
            'hired_at' => $assignmentDate->toDateTimeString(),
            'fired_at' => null,
        ]);
    }
});

it('assigns managers to a tag team through the same boundary', function () {
    $tagTeam = TagTeam::factory()->create();
    $manager = Manager::factory()->create();
    $assignmentDate = now()->subDay();

    resolve(ManagerAssignmentService::class)->assign(
        $tagTeam,
        new Collection([$manager]),
        $assignmentDate,
    );

    expect($tagTeam->currentManagers()->whereKey($manager->id)->exists())->toBeTrue();

    $this->assertDatabaseHas('tag_teams_managers', [
        'tag_team_id' => $tagTeam->id,
        'manager_id' => $manager->id,
        'hired_at' => $assignmentDate->toDateTimeString(),
        'fired_at' => null,
    ]);
});

it('synchronizes current managers while preserving relationship history', function () {
    $wrestler = Wrestler::factory()->create();
    $retainedManager = Manager::factory()->create();
    $removedManager = Manager::factory()->create();
    $addedManager = Manager::factory()->create();
    $service = resolve(ManagerAssignmentService::class);
    $service->assign(
        $wrestler,
        new Collection([$retainedManager, $removedManager]),
        now()->subDay(),
    );
    $changeDate = now();

    $service->synchronize(
        $wrestler,
        new Collection([$retainedManager, $addedManager]),
        $changeDate,
    );

    expect($wrestler->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing([$retainedManager->id, $addedManager->id])
        ->and($wrestler->previousManagers()->whereKey($removedManager->id)->exists())
        ->toBeTrue();

    $this->assertDatabaseHas('wrestlers_managers', [
        'wrestler_id' => $wrestler->id,
        'manager_id' => $removedManager->id,
        'fired_at' => $changeDate->toDateTimeString(),
    ]);
});

it('leaves manager assignments unchanged when synchronization is omitted', function () {
    $tagTeam = TagTeam::factory()->create();
    $manager = Manager::factory()->create();
    $service = resolve(ManagerAssignmentService::class);
    $service->assign($tagTeam, new Collection([$manager]), now()->subDay());

    $service->synchronize($tagTeam, null, now());

    expect($tagTeam->currentManagers()->whereKey($manager->id)->exists())->toBeTrue();
});

it('accepts an empty manager collection', function () {
    $wrestler = Wrestler::factory()->create();

    resolve(ManagerAssignmentService::class)->assign(
        $wrestler,
        new Collection(),
        now(),
    );

    expect($wrestler->managers()->exists())->toBeFalse();
});

it('preserves each manager assignment when a manager is reassigned', function () {
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    $managers = new Collection([$manager]);
    $noManagers = new Collection();
    $firstHiredAt = now()->subDays(4)->startOfSecond();
    $firstFiredAt = now()->subDays(3)->startOfSecond();
    $secondHiredAt = now()->subDays(2)->startOfSecond();
    $secondFiredAt = now()->subDay()->startOfSecond();
    $service = resolve(ManagerAssignmentService::class);

    $service->assign($wrestler, $managers, $firstHiredAt);
    $service->synchronize($wrestler, $noManagers, $firstFiredAt);
    $service->assign($wrestler, $managers, $secondHiredAt);
    $service->synchronize($wrestler, $noManagers, $secondFiredAt);

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
    $manager = Manager::factory()->create();
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

    resolve(ManagerAssignmentService::class)->endCurrentAssignments($manager, $assignmentEnd);

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
