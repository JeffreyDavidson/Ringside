<?php

use App\Actions\Titles\RetireAction;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->active()->create();
});

test('invoke calls retire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    RetireAction::shouldRun()->with($this->title);
});

test('a basic user cannot retire a title', function () {
    $this->actingAs(basicUser())
        ->patch(action([RetireController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot retire a title', function () {
    $this->patch(action([RetireController::class], $this->title))
        ->assertRedirect(route('login'));
});
