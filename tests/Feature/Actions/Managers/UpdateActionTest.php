<?php

test('updates a manager and redirects', function () {
    $manager = Manager::factory()->create([
        'first_name' => 'Dries',
        'last_name' => 'Vints',
    ]);

    $data = UpdateRequest::factory()->create([
        'first_name' => 'Taylor',
        'last_name' => 'Otwell',
        'start_date' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([ManagersController::class, 'edit'], $manager))
        ->patch(action([ManagersController::class, 'update'], $manager), $data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->first_name->toBe('Taylor')
        ->last_name->toBe('Otwell')
        ->employments->toBeEmpty();
});

test('update can employ an unemployed manager when start date is filled', function () {
    $dateTime = now()->toDateTimeString();
    $manager = Manager::factory()->unemployed()->create();
    $data = UpdateRequest::factory()->create(['start_date' => $dateTime]);

    $this->actingAs(administrator())
        ->from(action([ManagersController::class, 'edit'], $manager))
        ->patch(action([ManagersController::class, 'update'], $manager), $data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($dateTime);
});

test('update can employ a future employed manager when start date is filled', function () {
    $dateTime = now()->toDateTimeString();
    $manager = Manager::factory()->withFutureEmployment()->create();
    $data = UpdateRequest::factory()->create(['start_date' => $dateTime]);

    $this->actingAs(administrator())
        ->from(action([ManagersController::class, 'edit'], $manager))
        ->patch(action([ManagersController::class, 'update'], $manager), $data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($dateTime);
});
