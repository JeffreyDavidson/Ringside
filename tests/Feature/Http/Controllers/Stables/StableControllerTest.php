<?php

use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;
use App\Models\User;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([StablesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('stables.index')
        ->assertSeeLivewire('stables.stables-list');
});

test('a basic user cannot view tag teams index page', function () {
    $this->actingAs(basicUser())
        ->get(action([StablesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view tag teams index page', function () {
    $this->get(action([StablesController::class, 'index']))
        ->assertRedirect(route('login'));
});

test('show returns a view', function () {
    $stable = Stable::factory()->create();

    $this->actingAs(administrator())
        ->get(action([StablesController::class, 'show'], $stable))
        ->assertViewIs('stables.show')
        ->assertViewHas('stable', $stable);
});

test('a basic user can view their tag team profile', function () {
    $stable = Stable::factory()->for($user = basicUser())->create();

    $this->actingAs($user)
        ->get(action([StablesController::class, 'show'], $stable))
        ->assertOk();
});

test('a basic user cannot view another users tag team profile', function () {
    $stable = Stable::factory()->for(User::factory())->create();

    $this->actingAs(basicUser())
        ->get(action([StablesController::class, 'show'], $stable))
        ->assertForbidden();
});

test('a guest cannot view a stable profile', function () {
    $stable = Stable::factory()->create();

    $this->get(action([StablesController::class, 'show'], $stable))
        ->assertRedirect(route('login'));
});

test('deletes a stable and redirects', function () {
    $stable = Stable::factory()->create();

    $this->actingAs(administrator())
        ->delete(action([StablesController::class, 'destroy'], $stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    $this->assertSoftDeleted($stable);
});

test('a basic user cannot delete a stable', function () {
    $stable = Stable::factory()->create();

    $this->actingAs(basicUser())
        ->delete(action([StablesController::class, 'destroy'], $stable))
        ->assertForbidden();
});

test('a guest cannot delete a stable', function () {
    $stable = Stable::factory()->create();

    $this->delete(action([StablesController::class, 'destroy'], $stable))
        ->assertRedirect(route('login'));
});
