<?php

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->create();
});

test('edit returns a view', function () {
    actingAs(administrator())
        ->get(action([WrestlersController::class, 'edit'], $this->wrestler))
        ->assertOk()
        ->assertViewIs('wrestlers.edit')
        ->assertViewHas('wrestler', $this->wrestler);
});

test('a basic user cannot view the form for editing a wrestler', function () {
    actingAs(basicUser())
        ->get(action([WrestlersController::class, 'edit'], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a wrestler', function () {
    get(action([WrestlersController::class, 'edit'], $this->wrestler))
        ->assertRedirect(route('login'));
});
