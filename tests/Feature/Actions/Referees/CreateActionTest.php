<?php

test('store creates a referee and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'create']))
        ->post(action([RefereesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect(Referee::latest()->first())
        ->first_name->toBe('Taylor')
        ->last_name->toBe('Otwell')
        ->employments->toBeEmpty();
});

test('an employment is created for the referee if start date is filled in request', function () {
    $dateTime = Carbon::now()->toDateTimeString();
    $data = StoreRequest::factory()->create([
        'start_date' => $dateTime,
    ]);

    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'create']))
        ->post(action([RefereesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect(Referee::latest()->first())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($dateTime);
});
