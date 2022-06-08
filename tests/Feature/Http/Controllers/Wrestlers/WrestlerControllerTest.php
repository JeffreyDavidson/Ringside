<?php

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\User;
use App\Models\Wrestler;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([WrestlersController::class, 'index']))
        ->assertOk()
        ->assertViewIs('wrestlers.index')
        ->assertSeeLivewire('wrestlers.wrestlers-list');
});

test('a basic user cannot view wrestlers index page', function () {
    $this->actingAs(basicUser())
        ->get(action([WrestlersController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view wrestlers index page', function () {
    $this->get(action([WrestlersController::class, 'index']))
        ->assertRedirect(route('login'));
});

test('show returns a view', function () {
    $wrestler = Wrestler::factory()->create();

    $this->actingAs(administrator())
        ->get(action([WrestlersController::class, 'show'], $wrestler))
        ->assertViewIs('wrestlers.show')
        ->assertViewHas('wrestler', $wrestler);
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
    $wrestler = Wrestler::factory()->create();

    $this->get(action([WrestlersController::class, 'show'], $wrestler))
        ->assertRedirect(route('login'));
});

test('deletes a wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->create();

    $this->actingAs(administrator())
        ->delete(action([WrestlersController::class, 'destroy'], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    $this->assertSoftDeleted($wrestler);
});

test('a basic user cannot delete a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    $this->actingAs(basicUser())
        ->delete(action([WrestlersController::class, 'destroy'], $wrestler))
        ->assertForbidden();
});

test('a guest cannot delete a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    $this->delete(action([WrestlersController::class, 'destroy'], $wrestler))
        ->assertRedirect(route('login'));
});
