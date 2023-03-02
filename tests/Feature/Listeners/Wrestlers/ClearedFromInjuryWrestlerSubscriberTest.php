<?php

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerReinstated;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('clearing an injured wrestler on an unbookable tag team makes tag team bookable', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();
    $wrestler = Wrestler::factory()->injured()->onCurrentTagTeam($tagTeam)->create();

    expect($wrestler->currentTagTeam)
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);

    WrestlerReinstated::dispatch($wrestler);

    expect($wrestler->currentTagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);
});
