<?php

use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([RefereesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('referees.index')
        ->assertSeeLivewire('referees.referees-list');
});

test('a basic user cannot view referees index page', function () {
    $this->actingAs(basicUser())
        ->get(action([RefereesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view referees index page', function () {
    $this->get(action([RefereesController::class, 'index']))
        ->assertRedirect(route('login'));
});

test('show returns a view', function () {
    $referee = Referee::factory()->create();

    $this->actingAs(administrator())
        ->get(action([RefereesController::class, 'show'], $referee))
        ->assertViewIs('referees.show')
        ->assertViewHas('referee', $referee);
});

test('a basic user cannot view a referee profile', function () {
    $referee = Referee::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([RefereesController::class, 'show'], $referee))
        ->assertForbidden();
});

test('a guest cannot view a referee profile', function () {
    $referee = Referee::factory()->create();

    $this->get(action([RefereesController::class, 'show'], $referee))
        ->assertRedirect(route('login'));
});

test('deletes a referee and redirects', function () {
    $referee = Referee::factory()->create();

    $this->actingAs(administrator())
        ->delete(action([RefereesController::class, 'destroy'], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    $this->assertSoftDeleted($referee);
});

test('a basic user cannot delete a referee', function () {
    $referee = Referee::factory()->create();

    $this->actingAs(basicUser())
        ->delete(action([RefereesController::class, 'destroy'], $referee))
        ->assertForbidden();
});

test('a guest cannot delete a referee', function () {
    $referee = Referee::factory()->create();

    $this->delete(action([RefereesController::class, 'destroy'], $referee))
        ->assertRedirect(route('login'));
});
