<?php

declare(strict_types=1);

use App\Actions\Managers\AssignManagersAction;
use App\Actions\Managers\SynchronizeManagerAssignmentsAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

test('synchronization ends omitted current assignments', function () {
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    resolve(AssignManagersAction::class)->handle($wrestler, new Collection([$manager]), now()->subDay());
    resolve(SynchronizeManagerAssignmentsAction::class)->handle($wrestler, new Collection, now());
    expect($wrestler->currentManagers()->exists())->toBeFalse();
});

test('synchronization ends not-yet-started assignments on their own hire date', function () {
    $wrestler = Wrestler::factory()->create();
    $startedManager = Manager::factory()->create();
    $futureManager = Manager::factory()->create();
    $start = today()->addWeek();
    $wrestler->managers()->attach($startedManager, ['hired_at' => today()->subDay()]);
    $wrestler->managers()->attach($futureManager, ['hired_at' => $start]);

    resolve(SynchronizeManagerAssignmentsAction::class)->handle($wrestler, new Collection, today());

    expect($wrestler->managers()->whereKey($startedManager->getKey())->wherePivot('fired_at', today())->exists())->toBeTrue()
        ->and($wrestler->managers()->whereKey($futureManager->getKey())->wherePivot('fired_at', $start)->exists())->toBeTrue();
});
