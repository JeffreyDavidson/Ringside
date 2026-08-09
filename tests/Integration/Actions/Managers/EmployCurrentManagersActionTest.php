<?php

declare(strict_types=1);

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

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

    expect($wrestlerManager->isEmployed())->toBeTrue()
        ->and($tagTeamManager->isEmployed())->toBeTrue()
        ->and($futureManager->isEmployed())->toBeFalse()
        ->and($futureManager->hasFutureEmployment())->toBeTrue();

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $wrestlerManager->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $tagTeamManager->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});
