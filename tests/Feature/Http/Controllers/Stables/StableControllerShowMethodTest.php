<?php

use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;
use App\Models\User;

beforeEach(function () {
    $this->stable = Stable::factory()->create();
});

test('show returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([StablesController::class, 'show'], $this->stable))
        ->assertViewIs('stables.show')
        ->assertViewHas('stable', $this->stable);
});

test('a basic user can view their stable profile', function () {
    $stable = Stable::factory()->for($user = basicUser())->create();

    $this->actingAs($user)
        ->get(action([StablesController::class, 'show'], $stable))
        ->assertOk();
});

test('a basic user cannot view another users stable profile', function () {
    $stable = Stable::factory()->for(User::factory())->create();

    $this->actingAs(basicUser())
        ->get(action([StablesController::class, 'show'], $stable))
        ->assertForbidden();
});

test('a guest cannot view a stable profile', function () {
    $this->get(action([StablesController::class, 'show'], $this->stable))
        ->assertRedirect(route('login'));
});
