<?php

declare(strict_types=1);

use App\Actions\Managers\AssignManagersAction;
use App\Actions\Managers\EndManagerAssignmentsAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

test('ending assignments dates current relationships', function () {
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    resolve(AssignManagersAction::class)->handle($wrestler, new Collection([$manager]), now()->subDay());
    resolve(EndManagerAssignmentsAction::class)->handle($wrestler, now());
    expect($wrestler->currentManagers()->exists())->toBeFalse();
});
