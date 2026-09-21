<?php

declare(strict_types=1);

use App\Actions\TagTeams\SuspendCurrentMembersAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;

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

        expect($wrestler->currentSuspension()->exists())->toBeTrue();

        $transition = $wrestler->lifecycleTransitions()
            ->where('dimension', LifecycleDimension::Suspension)
            ->sole();
        expect($transition->transition)->toBe(LifecycleTransitionType::Suspended)
            ->and($transition->effective_at->toDateTimeString())->toBe($suspensionDate->toDateTimeString());

        $this->assertDatabaseHas('suspensions', [
            'suspendable_id' => $wrestler->id,
            'suspendable_type' => $wrestler->getMorphClass(),
            'started_at' => $suspensionDate->toDateTimeString(),
            'ended_at' => null,
        ]);
    }

    $manager->refresh();
    $alreadySuspendedManager->refresh();

    expect($manager->currentSuspension()->exists())->toBeTrue()
        ->and($alreadySuspendedManager->suspensions()->count())->toBe($existingSuspensionCount);

    $managerTransition = $manager->lifecycleTransitions()
        ->where('dimension', LifecycleDimension::Suspension)
        ->sole();
    expect($managerTransition->transition)->toBe(LifecycleTransitionType::Suspended)
        ->and($alreadySuspendedManager->lifecycleTransitions()->count())->toBe(0);

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});
