<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployCurrentWrestlersAction;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it employs unemployed current wrestlers', function () {
    $tagTeam = TagTeam::factory()->unemployed()->create();
    $employmentDate = now()->subDay();
    $wrestlers = $tagTeam->currentWrestlers()->get();
    $futureWrestler = Wrestler::factory()->withFutureEmployment()->create();

    $tagTeam->wrestlers()->attach($futureWrestler, ['joined_at' => now()->subMonth()]);

    resolve(EmployCurrentWrestlersAction::class)
        ->handle($tagTeam, $employmentDate);

    foreach ($wrestlers as $wrestler) {
        $wrestler->refresh();

        expect($wrestler->isEmployed())->toBeTrue();

        $this->assertDatabaseHas('employments', [
            'employable_id' => $wrestler->id,
            'started_at' => $employmentDate->toDateTimeString(),
            'ended_at' => null,
        ]);
    }

    $futureWrestler->refresh();

    expect($futureWrestler->isEmployed())->toBeFalse()
        ->and($futureWrestler->futureEmployment()->exists())->toBeTrue();
});
