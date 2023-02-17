<?php

use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->referee = Referee::factory()->create();
});

test('edit returns a view', function () {
    actingAs(administrator())
        ->get(action([RefereesController::class, 'edit'], $this->referee))
        ->assertStatus(200)
        ->assertViewIs('referees.edit')
        ->assertViewHas('referee', $this->referee);
});

test('a basic user cannot view the form for editing a referee', function () {
    actingAs(basicUser())
        ->get(action([RefereesController::class, 'edit'], $this->referee))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a referee', function () {
    get(action([RefereesController::class, 'edit'], $this->referee))
        ->assertRedirect(route('login'));
});
