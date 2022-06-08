<?php

use App\Http\Controllers\Referees\RefereesController;
use App\Http\Requests\Referees\UpdateRequest;
use App\Models\Referee;

test('edit returns a view', function () {
    $referee = Referee::factory()->create();

    $this->actingAs(administrator())
        ->get(action([RefereesController::class, 'edit'], $referee))
        ->assertStatus(200)
        ->assertViewIs('referees.edit')
        ->assertViewHas('referee', $referee);
});

test('a basic user cannot view the form for editing a referee', function () {
    $referee = Referee::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([RefereesController::class, 'edit'], $referee))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a referee', function () {
    $referee = Referee::factory()->create();

    $this->get(action([RefereesController::class, 'edit'], $referee))
        ->assertRedirect(route('login'));
});

test('updates a referee and redirects', function () {
    $referee = Referee::factory()->create([
        'first_name' => 'Dries',
        'last_name' => 'Vints',
    ]);

    $data = UpdateRequest::factory()->create([
        'first_name' => 'Taylor',
        'last_name' => 'Otwell',
        'started_at' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'edit'], $referee))
        ->patch(action([RefereesController::class, 'update'], $referee), $data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect(Referee::latest()->first())
        ->first_name->toBe('Taylor')
        ->last_name->toBe('Otwell')
        ->employments->toBeEmpty();
});

test('update can employ an unemployed referee when started at is filled', function () {
    $now = now();
    $referee = Referee::factory()->unemployed()->create();
    $data = UpdateRequest::factory()->create(['started_at' => $now->toDateTimeString()]);

    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'edit'], $referee))
        ->patch(action([RefereesController::class, 'update'], $referee), $data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($now->toDateTimeString());
});

test('update can employ a future employed referee when started at is filled', function () {
    $now = now();
    $referee = Referee::factory()->withFutureEmployment()->create();
    $data = UpdateRequest::factory()->create(['started_at' => $now->toDateTimeString()]);

    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'edit'], $referee))
        ->patch(action([RefereesController::class, 'update'], $referee), $data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($now->toDateTimeString());
});

test('a basic user cannot update a referee', function () {
    $referee = Referee::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->actingAs(basicUser())
        ->patch(action([RefereesController::class, 'update'], $referee), $data)
        ->assertForbidden();
});

test('a guest cannot update a referee', function () {
    $referee = Referee::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->patch(action([RefereesController::class, 'update'], $referee), $data)
        ->assertRedirect(route('login'));
});
