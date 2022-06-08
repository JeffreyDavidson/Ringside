<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\TagTeam;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->bookable()->create();
});

test('invoke suspends a bookable wrestler and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($this->wrestler->fresh())
        ->suspensions->toHaveCount(1)
        ->status->toBe(WrestlerStatus::SUSPENDED);
});

test('suspending a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = $tagTeam->currentWrestlers()->first();

    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $wrestler));

    expect($tagTeam->fresh())
        ->status->toBe(TagTeamStatus::UNBOOKABLE);
});

test('a basic user cannot suspend a bookable wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([SuspendController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot suspend a bookable wrestler', function () {
    $this->patch(action([SuspendController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for suspending a non suspendable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $wrestler));
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
