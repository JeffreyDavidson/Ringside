<?php

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerReleased;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('releasing a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->onCurrentTagTeam($tagTeam)->create();

    expect($wrestler->currentTagTeam)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);

    WrestlerReleased::dispatch($wrestler, now());

    expect($wrestler->fresh())->currentTagTeam->toBeNull();
    expect($wrestler->fresh())->previousTagTeam
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);
});
