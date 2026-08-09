<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployCurrentWrestlersAction;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it employs unemployed current wrestlers', function () {
    $tagTeam = TagTeam::factory()->unemployed()->create();
    $employmentDate = now()->subDay();
    $wrestlers = $tagTeam->currentWrestlers()->get();

    resolve(EmployCurrentWrestlersAction::class)
        ->handle($tagTeam, $employmentDate);

    foreach ($wrestlers as $wrestler) {
        $wrestler->refresh();

        expect($wrestler->isEmployed())->toBeTrue();

        $this->assertDatabaseHas('wrestlers_employments', [
            'wrestler_id' => $wrestler->id,
            'started_at' => $employmentDate->toDateTimeString(),
            'ended_at' => null,
        ]);
    }
});
