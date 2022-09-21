<?php

test('invoke unretires a retired title and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($this->title->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(TitleStatus::ACTIVE);
});

test('invoke throws exception for unretiring a non unretirable title', function ($factoryState) {
    $this->withoutExceptionHandling();

    $title = Title::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $title));
})->throws(CannotBeUnretiredException::class)->with([
    'active',
    'inactive',
    'withFutureActivation',
    'unactivated',
]);
