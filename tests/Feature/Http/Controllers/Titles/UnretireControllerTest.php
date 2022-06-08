<?php

use App\Enums\TitleStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Titles\UnretireController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->retired()->create();
});

test('invoke unretires a retired title and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($this->title->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toBe(TitleStatus::ACTIVE);
});

test('a basic user cannot unretire a title', function () {
    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot unretire a title', function () {
    $this->patch(action([UnretireController::class], $this->title))
        ->assertRedirect(route('login'));
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
