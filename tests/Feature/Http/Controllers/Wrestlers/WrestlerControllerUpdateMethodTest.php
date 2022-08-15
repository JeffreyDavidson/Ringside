<?php

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\Wrestler;

test('edit returns a view', function () {
    $wrestler = Wrestler::factory()->create();

    $this->actingAs(administrator())
        ->get(action([WrestlersController::class, 'edit'], $wrestler))
        ->assertStatus(200)
        ->assertViewIs('wrestlers.edit')
        ->assertViewHas('wrestler', $wrestler);
});

test('a basic user cannot view the form for editing a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([WrestlersController::class, 'edit'], $wrestler))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    $this->get(action([WrestlersController::class, 'edit'], $wrestler))
        ->assertRedirect(route('login'));
});

test('updates a wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->create([
        'name' => 'Old Wrestler Name',
        'height' => 81,
        'weight' => 300,
        'hometown' => 'Old Location',
    ]);

    $data = UpdateRequest::factory()->create([
        'name' => 'Example Wrestler Name',
        'feet' => 6,
        'inches' => 10,
        'weight' => 300,
        'hometown' => 'Laraville, New York',
        'signature_move' => null,
        'start_date' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([WrestlersController::class, 'edit'], $wrestler))
        ->patch(action([WrestlersController::class, 'update'], $wrestler), $data)
        ->assertValid()
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->name->toBe('Example Wrestler Name')
        ->height->toBe(82)
        ->weight->toBe(300)
        ->hometown->toBe('Laraville, New York')
        ->employments->toBeEmpty();
});

test('update can employ an unemployed wrestler when start date is filled', function () {
    $now = now();
    $wrestler = Wrestler::factory()->unemployed()->create();
    $data = UpdateRequest::factory()->create(['start_date' => $now->toDateTimeString()]);

    $this->actingAs(administrator())
        ->from(action([WrestlersController::class, 'edit'], $wrestler))
        ->patch(action([WrestlersController::class, 'update'], $wrestler), $data)
        ->assertValid()
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($now->toDateTimeString());
});

test('update can employ a future employed wrestler when start date is filled', function () {
    $now = now();
    $wrestler = Wrestler::factory()->withFutureEmployment()->create();
    $data = UpdateRequest::factory()->create(['start_date' => $now->toDateTimeString()]);

    $this->actingAs(administrator())
        ->from(action([WrestlersController::class, 'edit'], $wrestler))
        ->patch(action([WrestlersController::class, 'update'], $wrestler), $data)
        ->assertValid()
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($now->toDateTimeString());
});

test('a basic user cannot update a wrestler', function () {
    $wrestler = Wrestler::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->actingAs(basicUser())
        ->patch(action([WrestlersController::class, 'update'], $wrestler), $data)
        ->assertForbidden();
});

test('a guest cannot update a wrestler', function () {
    $wrestler = Wrestler::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->patch(action([WrestlersController::class, 'update'], $wrestler), $data)
        ->assertRedirect(route('login'));
});
