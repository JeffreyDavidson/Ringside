<?php

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\User;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->create();
});

test('show returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([WrestlersController::class, 'show'], $this->wrestler))
        ->assertViewIs('wrestlers.show')
        ->assertViewHas('wrestler', $this->wrestler);
});

test('a basic user can view their wrestler profile', function () {
    $wrestler = Wrestler::factory()->for($user = basicUser())->create();

    $this->actingAs($user)
        ->get(action([WrestlersController::class, 'show'], $wrestler))
        ->assertOk();
});

test('a basic user cannot view another users wrestler profile', function () {
    $wrestler = Wrestler::factory()->for(User::factory())->create();

    $this->actingAs(basicUser())
        ->get(action([WrestlersController::class, 'show'], $wrestler))
        ->assertForbidden();
});

test('a guest cannot view a wrestler profile', function () {
    $this->get(action([WrestlersController::class, 'show'], $this->wrestler))
        ->assertRedirect(route('login'));
});
