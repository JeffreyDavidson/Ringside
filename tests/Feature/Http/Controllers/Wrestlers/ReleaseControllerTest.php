<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Wrestlers\ReleaseController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('invoke releases a bookable wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->bookable()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toBe(WrestlerStatus::RELEASED);
});

test('invoke releases an injured wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->employments->last()->ended_at->not->toBeNull()
        ->injuries->last()->ended_at->not->toBeNull()
        ->status->toBe(WrestlerStatus::RELEASED);
});

test('invoke releases an suspended wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->employments->last()->ended_at->not->toBeNull()
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toBe(WrestlerStatus::RELEASED);
});

test('releasing a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $this->withoutExceptionHandling();

    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = $tagTeam->currentWrestlers()->first();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($tagTeam->fresh())
        ->status->toBe(TagTeamStatus::UNBOOKABLE);
});

test('a basic user cannot release a bookable wrestler', function () {
    $wrestler = Wrestler::factory()->bookable()->create();

    $this->actingAs(basicUser())
        ->patch(action([ReleaseController::class], $wrestler))
        ->assertForbidden();
});

test('a guest cannot release a bookable wrestler', function () {
    $wrestler = Wrestler::factory()->bookable()->create();

    $this->patch(action([ReleaseController::class], $wrestler))
        ->assertRedirect(route('login'));
});

test('invoke throws an exception for releasing a non releasable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $wrestler));
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
