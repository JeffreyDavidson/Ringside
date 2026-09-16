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
    resolve(SynchronizeManagerAssignmentsAction::class)->handle($wrestler, new Collection(), now());
    expect($wrestler->currentManagers()->exists())->toBeFalse();
});
