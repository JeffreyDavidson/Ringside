<?php

declare(strict_types=1);

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('it skips retired managers and employs the rest', function () {
    $tagTeam = TagTeam::factory()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $eligibleManager = Manager::factory()->create();

    $tagTeam->managers()->attach([$retiredManager->id, $eligibleManager->id], ['hired_at' => now()->subMonth()]);

    resolve(EmployCurrentManagersAction::class)
        ->handle($tagTeam, now());

    expect($retiredManager->refresh()->currentEmployment()->exists())->toBeFalse()
        ->and($retiredManager->currentRetirement()->exists())->toBeTrue()
        ->and($eligibleManager->refresh()->currentEmployment()->exists())->toBeTrue();
});

test('it employs unemployed managers for each manageable roster type', function () {
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $wrestlerManager = Manager::factory()->create();
    $tagTeamManager = Manager::factory()->create();
    $futureManager = Manager::factory()->withFutureEmployment()->create();
    $employmentDate = now()->subDay();

    $wrestler->managers()->attach($wrestlerManager, ['hired_at' => now()->subMonth()]);
    $tagTeam->managers()->attach($tagTeamManager, ['hired_at' => now()->subMonth()]);
    $tagTeam->managers()->attach($futureManager, ['hired_at' => now()->subMonth()]);

    $action = resolve(EmployCurrentManagersAction::class);
    $action->handle($wrestler, $employmentDate);
    $action->handle($tagTeam, $employmentDate);

    $wrestlerManager->refresh();
    $tagTeamManager->refresh();
    $futureManager->refresh();

    expect($wrestlerManager->currentEmployment()->exists())->toBeTrue()
        ->and($tagTeamManager->currentEmployment()->exists())->toBeTrue()
        ->and($futureManager->currentEmployment()->exists())->toBeFalse()
        ->and($futureManager->futureEmployment()->exists())->toBeTrue();

    $this->assertDatabaseHas('employments', [
        'employable_id' => $wrestlerManager->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
    $this->assertDatabaseHas('employments', [
        'employable_id' => $tagTeamManager->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});
