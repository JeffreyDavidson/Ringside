<?php

use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Wrestlers\EmployController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

test('invoke employs an unemployed wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->employments->toHaveCount(1)
        ->status->toBe(WrestlerStatus::BOOKABLE);
});

test('invoke employs a future employed wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->withFutureEmployment()->create();
    $startedAt = $wrestler->employments->last()->started_at;

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->currentEmployment->started_at->toBeLessThan($startedAt)
        ->status->toBe(WrestlerStatus::BOOKABLE);
});

test('invoke employs a released wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->released()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->employments->toHaveCount(2)
        ->status->toBe(WrestlerStatus::BOOKABLE);
});

test('a basic user cannot employ a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    $this->actingAs(basicUser())
        ->patch(action([EmployController::class], $wrestler))
        ->assertForbidden();
});

test('a guest user cannot employ a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    $this->patch(action([EmployController::class], $wrestler))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for employing a non employable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $wrestler));
})->throws(CannotBeEmployedException::class)->with([
    'suspended',
    'injured',
    'bookable',
    'retired',
]);
