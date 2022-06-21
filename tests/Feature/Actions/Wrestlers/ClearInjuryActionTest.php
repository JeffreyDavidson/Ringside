<?php

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;

test('run makes injured wrestler not injured using current datetime', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $now = now();

    ClearInjuryAction::run($wrestler);

    expect($wrestler->fresh())
        ->isInjured()->toBeFalse()
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
