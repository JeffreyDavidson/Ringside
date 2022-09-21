<?php

test('store creates a manager and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([ManagersController::class, 'create']))
        ->post(action([ManagersController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect(Manager::latest()->first())
        ->first_name->toBe('Taylor')
        ->last_name->toBe('Otwell')
        ->employments->toBeEmpty();
});

test('an employment is created for the manager if start date is filled in request', function () {
    $dateTime = Carbon::now()->toDateTimeString();
    $data = StoreRequest::factory()->create([
        'start_date' => $dateTime,
    ]);

    $this->actingAs(administrator())
        ->from(action([ManagersController::class, 'create']))
        ->post(action([ManagersController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect(Manager::latest()->first())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toEqual($dateTime);
});
