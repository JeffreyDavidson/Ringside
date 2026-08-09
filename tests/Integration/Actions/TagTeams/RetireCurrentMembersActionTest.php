<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireCurrentMembersAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

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

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
    $this->assertDatabaseHas('managers_retirements', [
        'manager_id' => $manager->id,
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});
