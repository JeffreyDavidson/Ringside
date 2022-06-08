<?php

use App\Enums\TitleStatus;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Titles\DeactivateController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

test('invoke deactivates an active title and redirects', function () {
    $title = Title::factory()->active()->create();

    $this->actingAs(administrator())
        ->patch(action([DeactivateController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($title->fresh())
        ->activations->last()->ended_at->not->toBeNull()
        ->status->toBe(TitleStatus::INACTIVE);
});

test('a basic user cannot deactivate an active title', function () {
    $title = Title::factory()->active()->create();

    $this->actingAs(basicUser())
        ->patch(action([DeactivateController::class], $title))
        ->assertForbidden();
});

test('a guest cannot deactivates a titles', function () {
    $title = Title::factory()->active()->create();

    $this->patch(action([DeactivateController::class], $title))
        ->assertRedirect(route('login'));
});

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
