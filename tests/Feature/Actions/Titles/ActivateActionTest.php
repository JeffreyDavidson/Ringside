<?php

test('invoke calls activate action and redirects', function () {
    $title = Title::factory()->unactivated()->create();

    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($title->fresh())
        ->activations->toHaveCount(1)
        ->status->toMatchObject(TitleStatus::ACTIVE);
});

test('invoke activates a future activated title and redirects', function () {
    $title = Title::factory()->withFutureActivation()->create();

    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($title->fresh())
        ->activations->toHaveCount(1)
        ->status->toMatchObject(TitleStatus::ACTIVE);
});

test('invoke throws exception for unretiring a non unretirable title', function ($factoryState) {
    $this->withoutExceptionHandling();

    $title = Title::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $title));
})->throws(CannotBeActivatedException::class)->with([
    'active',
    'retired',
]);
