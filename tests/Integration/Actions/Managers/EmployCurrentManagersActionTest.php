<?php

declare(strict_types=1);

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
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
