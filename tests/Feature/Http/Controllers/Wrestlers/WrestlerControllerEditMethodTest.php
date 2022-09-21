<?php

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->create();
});

test('edit returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([WrestlersController::class, 'edit'], $this->wrestler))
        ->assertStatus(200)
        ->assertViewIs('wrestlers.edit')
        ->assertViewHas('wrestler', $this->wrestler);
});

test('a basic user cannot view the form for editing a wrestler', function () {
    $this->actingAs(basicUser())
        ->get(action([WrestlersController::class, 'edit'], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a wrestler', function () {
    $this->get(action([WrestlersController::class, 'edit'], $this->wrestler))
        ->assertRedirect(route('login'));
});
