<?php

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;

test('run clears an injury a wrestler', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    ClearInjuryAction::run($wrestler);

    expect($wrestler->fresh())
        ->status->toBe(WrestlerStatus::BOOKABLE)
        ->injuries->last()->ended_at->toEqual($now->toDateTimeString());
});

test('run makes injured wrestler not injured using specific datetime', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $recoveryDate = Carbon::parse('2022-05-27 12:00:00');

    ClearInjuryAction::run($wrestler, $recoveryDate);

    expect($wrestler->fresh())
        ->isInjured()->toBeFalse()
        ->injuries->last()->ended_at->toEqual($recoveryDate);
});

test('clearing an injured wrestler on an unbookable tag team makes tag team bookable', function () {
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($this->wrestler, ['joined_at' => Carbon::yesterday()->toDateTimeString()])
        ->hasAttached($bookableWrestler, ['joined_at' => Carbon::yesterday()->toDateTimeString()])
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->create();

    ClearInjuryAction::run($this->wrestler);

    expect($this->wrestler->fresh())
        ->status->toMatchObject(WrestlerStatus::BOOKABLE);

    expect($tagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);
});

test('it throws exception for injuring a non injurable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    ClearInjuryAction::run($wrestler);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    WrestlerStatus::UNEMPLOYED,
    WrestlerStatus::RELEASED,
    WrestlerStatus::FUTURE_EMPLOYMENT,
    WrestlerStatus::BOOKABLE,
    WrestlerStatus::RETIRED,
    WrestlerStatus::SUSPENDED,
]);
