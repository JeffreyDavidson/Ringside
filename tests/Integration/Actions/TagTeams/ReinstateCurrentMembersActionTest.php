<?php

declare(strict_types=1);

use App\Actions\TagTeams\ReinstateCurrentMembersAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it reinstates suspended current wrestlers and managers', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $manager = Manager::factory()->suspended()->create();
    $activeManager = Manager::factory()->employed()->create();
    $reinstatementDate = now()->subDay();

    $tagTeam->managers()->attach($manager, ['hired_at' => now()->subMonth()]);
    $tagTeam->managers()->attach($activeManager, ['hired_at' => now()->subMonth()]);
    $wrestlers = $tagTeam->currentWrestlers()->get();
    $activeManagerSuspensionCount = $activeManager->suspensions()->count();

    resolve(ReinstateCurrentMembersAction::class)
        ->handle($tagTeam, $reinstatementDate);

    foreach ($wrestlers as $wrestler) {
        $wrestler->refresh();

        expect($wrestler->currentSuspension()->exists())->toBeFalse();

        $transition = $wrestler->lifecycleTransitions()
            ->where('dimension', LifecycleDimension::Suspension)
            ->sole();
        expect($transition->transition)->toBe(LifecycleTransitionType::Reinstated)
            ->and($transition->effective_at->toDateTimeString())->toBe($reinstatementDate->toDateTimeString());

        $this->assertDatabaseHas('suspensions', [
            'suspendable_id' => $wrestler->id,
            'suspendable_type' => $wrestler->getMorphClass(),
            'ended_at' => $reinstatementDate->toDateTimeString(),
        ]);
    }

    $manager->refresh();
    $activeManager->refresh();

    expect($manager->currentSuspension()->exists())->toBeFalse()
        ->and($activeManager->suspensions()->count())->toBe($activeManagerSuspensionCount);

    $managerTransition = $manager->lifecycleTransitions()
        ->where('dimension', LifecycleDimension::Suspension)
        ->sole();
    expect($managerTransition->transition)->toBe(LifecycleTransitionType::Reinstated)
        ->and($activeManager->lifecycleTransitions()->count())->toBe(0);

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $manager->id,
        'suspendable_type' => $manager->getMorphClass(),
        'ended_at' => $reinstatementDate->toDateTimeString(),
    ]);
});
