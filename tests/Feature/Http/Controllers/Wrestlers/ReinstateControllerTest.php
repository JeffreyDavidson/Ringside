<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Employment;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->suspended()->create();
});

test('invoke reinstates a suspended wrestler and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($this->wrestler->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toBe(WrestlerStatus::BOOKABLE);
});

test('reinstating a suspended wrestler on an unbookable tag team makes tag team bookable', function () {
    $tagTeam = TagTeam::factory()
        ->hasAttached($suspendedWrestler = Wrestler::factory()->suspended()->create())
        ->hasAttached(Wrestler::factory()->bookable())
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $suspendedWrestler));

    expect($tagTeam->fresh())
        ->status->toBe(TagTeamStatus::BOOKABLE);
});

test('a basic user cannot reinstate a suspended wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot reinstate a suspended wrestler', function () {
    $this->patch(action([ReinstateController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for reinstating a non reinstatable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $wrestler));
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
