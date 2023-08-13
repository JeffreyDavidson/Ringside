<?php

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerClearedFromInjury;
use App\Events\Wrestlers\WrestlerInjured;
use App\Events\Wrestlers\WrestlerReinstated;
use App\Events\Wrestlers\WrestlerReleased;
use App\Events\Wrestlers\WrestlerRetired;
use App\Events\Wrestlers\WrestlerSuspended;
use App\Listeners\WrestlerSubscriber;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('clearing an injured wrestler on an unbookable tag team makes tag team bookable', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();
    $wrestler = Wrestler::factory()->injured()->onCurrentTagTeam($tagTeam)->create();

    expect($wrestler->currentTagTeam)
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);

    app(WrestlerSubscriber::class)->handleTagTeamWrestlerClearedFromInjury(new WrestlerClearedFromInjury($wrestler, now()));

    expect($wrestler->currentTagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);
});

test('injuring a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->onCurrentTagTeam($tagTeam)->create();

    expect($wrestler->currentTagTeam)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);

    app(WrestlerSubscriber::class)->handleTagTeamWrestlerInjured(new WrestlerInjured($wrestler, now()));

    expect($wrestler->currentTagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);
});

test('reinstating a suspended wrestler on an unbookable tag team makes tag team bookable', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();
    $wrestler = Wrestler::factory()->suspended()->onCurrentTagTeam($tagTeam)->create();

    expect($wrestler->currentTagTeam)
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);

    app(WrestlerSubscriber::class)->handleTagTeamWrestlerReinstated(new WrestlerReinstated($wrestler, now()));

    expect($wrestler->currentTagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);
});

test('releasing a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->onCurrentTagTeam($tagTeam)->create();

    expect($wrestler->currentTagTeam)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);

    app(WrestlerSubscriber::class)->handleTagTeamWrestlerReleased(new WrestlerReleased($wrestler, now()));

    expect($wrestler->fresh())->currentTagTeam->toBeNull();
    expect($wrestler->fresh())->previousTagTeam
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);
});

test('suspending a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->onCurrentTagTeam($tagTeam)->create();

    expect($wrestler->currentTagTeam)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);

    app(WrestlerSubscriber::class)->handleTagTeamWrestlerSuspended(new WrestlerSuspended($wrestler, now()));

    expect($wrestler->currentTagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);
});

test('retiring a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->onCurrentTagTeam($tagTeam)->create();

    expect($wrestler->currentTagTeam)
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);

    app(WrestlerSubscriber::class)->handleTagTeamWrestlerRetired(new WrestlerRetired($wrestler, now()));

    expect($wrestler->currentTagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);
});
