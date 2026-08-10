<?php

declare(strict_types=1);

use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerManager;
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
