<?php

declare(strict_types=1);

use App\Actions\TagTeams\SuspendCurrentMembersAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it suspends eligible current wrestlers and managers', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $manager = Manager::factory()->employed()->create();
    $alreadySuspendedManager = Manager::factory()->suspended()->create();
    $suspensionDate = now()->subDay();

    $tagTeam->managers()->attach($manager, ['hired_at' => now()->subMonth()]);
    $tagTeam->managers()->attach($alreadySuspendedManager, ['hired_at' => now()->subMonth()]);
    $wrestlers = $tagTeam->currentWrestlers()->get();
    $existingSuspensionCount = $alreadySuspendedManager->suspensions()->count();

    resolve(SuspendCurrentMembersAction::class)
        ->handle($tagTeam, $suspensionDate);

    foreach ($wrestlers as $wrestler) {
        $wrestler->refresh();

        expect($wrestler->isSuspended())->toBeTrue();

        $this->assertDatabaseHas('suspensions', [
            'suspendable_id' => $wrestler->id,
            'suspendable_type' => $wrestler->getMorphClass(),
            'started_at' => $suspensionDate->toDateTimeString(),
            'ended_at' => null,
        ]);
    }

    $manager->refresh();
    $alreadySuspendedManager->refresh();

    expect($manager->isSuspended())->toBeTrue()
        ->and($alreadySuspendedManager->suspensions()->count())->toBe($existingSuspensionCount);

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});
