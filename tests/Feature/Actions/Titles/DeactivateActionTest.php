<?php

test('invoke throws exception for deactivating a non deactivatable title', function ($factoryState) {
    $this->withoutExceptionHandling();

    $title = Title::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([DeactivateController::class], $title));
})->throws(CannotBeDeactivatedException::class)->with([
    'unactivated',
    'withFutureActivation',
    'inactive',
    'retired',
]);
