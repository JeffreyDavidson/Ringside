<?php

use App\Enums\TitleStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

test('invoke retires an active title and redirects', function () {
    $title = Title::factory()->active()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($title->fresh())
        ->retirements->toHaveCount(1)
        ->status->toBe(TitleStatus::RETIRED);
});

test('invoke retires an inactive title and redirects', function () {
    $title = Title::factory()->inactive()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($title->fresh())
        ->retirements->toHaveCount(1)
        ->status->toBe(TitleStatus::RETIRED);
});

test('a basic user cannot retire an active title', function () {
    $title = Title::factory()->active()->create();

    $this->actingAs(basicUser())
        ->patch(action([RetireController::class], $title))
        ->assertForbidden();
});

test('a guest cannot retire an active title', function () {
    $title = Title::factory()->active()->create();

    $this->patch(action([RetireController::class], $title))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for unretiring a non unretirable title', function ($factoryState) {
    $this->withoutExceptionHandling();

    $title = Title::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $title));
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureActivation',
    'unactivated',
]);
