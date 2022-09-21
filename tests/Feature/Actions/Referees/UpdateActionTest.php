<?php

test('update calls update action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'edit'], $this->referee))
        ->patch(action([RefereesController::class, 'update'], $this->referee), $this->data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect(Referee::latest()->first())
        ->first_name->toBe('Taylor')
        ->last_name->toBe('Otwell')
        ->employments->toBeEmpty();
});

test('update can employ an unemployed referee when start date is filled', function () {
    $now = now();
    $referee = Referee::factory()->unemployed()->create();
    $data = UpdateRequest::factory()->create(['start_date' => $now->toDateTimeString()]);

    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'edit'], $referee))
        ->patch(action([RefereesController::class, 'update'], $referee), $data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($now->toDateTimeString());
});

test('update can employ a future employed referee when start date is filled', function () {
    $now = now();
    $referee = Referee::factory()->withFutureEmployment()->create();
    $data = UpdateRequest::factory()->create(['start_date' => $now->toDateTimeString()]);

    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'edit'], $referee))
        ->patch(action([RefereesController::class, 'update'], $referee), $data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($now->toDateTimeString());
});
