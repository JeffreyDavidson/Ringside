<?php

test('invoke retires an active title and redirects', function () {
    $title = Title::factory()->active()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($title->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(TitleStatus::RETIRED);
});

test('invoke retires an inactive title and redirects', function () {
    $title = Title::factory()->inactive()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($title->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(TitleStatus::RETIRED);
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
