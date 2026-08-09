<?php

declare(strict_types=1);

use App\Actions\TagTeams\UnretireCurrentMembersAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it unretires retired current wrestlers and managers without employing them', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $manager = Manager::factory()->retired()->create();
    $unretirementDate = now()->subDay();

    $tagTeam->managers()->attach($manager, ['hired_at' => now()->subMonth()]);
    $wrestlers = $tagTeam->currentWrestlers()->get();

    resolve(UnretireCurrentMembersAction::class)
        ->handle($tagTeam, $unretirementDate);

    foreach ($wrestlers as $wrestler) {
        $wrestler->refresh();

        expect($wrestler->isRetired())->toBeFalse()
            ->and($wrestler->isEmployed())->toBeFalse();

        $this->assertDatabaseHas('wrestlers_retirements', [
            'wrestler_id' => $wrestler->id,
            'ended_at' => $unretirementDate->toDateTimeString(),
        ]);
    }

    $manager->refresh();

    expect($manager->isRetired())->toBeFalse()
        ->and($manager->isEmployed())->toBeFalse();

    $this->assertDatabaseHas('managers_retirements', [
        'manager_id' => $manager->id,
        'ended_at' => $unretirementDate->toDateTimeString(),
    ]);
});
