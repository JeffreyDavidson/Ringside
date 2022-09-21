<?php

use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->create();
});

test('edit returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([StablesController::class, 'edit'], $this->stable))
        ->assertStatus(200)
        ->assertViewIs('stables.edit')
        ->assertViewHas('stable', $this->stable);
});

test('a basic user cannot view the form for editing a stable', function () {
    $this->actingAs(basicUser())
        ->get(action([StablesController::class, 'edit'], $this->stable))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a stable', function () {
    $this->get(action([StablesController::class, 'edit'], $this->stable))
        ->assertRedirect(route('login'));
});
