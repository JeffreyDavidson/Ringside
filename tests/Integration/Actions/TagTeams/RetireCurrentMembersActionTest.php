<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireCurrentMembersAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires eligible current wrestlers and managers', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $manager = Manager::factory()->employed()->create();
    $retirementDate = now()->subDay();

    $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => now()->subMonth()]);
    $tagTeam->managers()->attach($manager, ['hired_at' => now()->subMonth()]);

    resolve(RetireCurrentMembersAction::class)
        ->handle($tagTeam, $retirementDate);

    $wrestler->refresh();
    $manager->refresh();

    expect($wrestler->isRetired())->toBeTrue()
        ->and($manager->isRetired())->toBeTrue();

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $manager->id,
        'retirable_type' => $manager->getMorphClass(),
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});
