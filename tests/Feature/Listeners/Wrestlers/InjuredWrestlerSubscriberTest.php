<?php

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerInjured;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('injuring a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->onCurrentTagTeam($tagTeam)->create();

    expect($wrestler->currentTagTeam)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);

    WrestlerInjured::dispatch($wrestler);

    expect($wrestler->currentTagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);
});
