<?php

test('store calls create action and redirects', function () {
    $data = StoreRequest::factory()->create([
        'name' => 'Example Title',
        'activation_date' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'create']))
        ->post(action([TitlesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect(Title::latest()->first())
        ->name->toBe('Example Title')
        ->activations->toBeEmpty();
});

test('an activation is created for the title if activation date is filled in request', function () {
    $activationDate = now()->toDateTimeString();
    $data = StoreRequest::factory()->create([
        'activation_date' => $activationDate,
    ]);

    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'create']))
        ->post(action([TitlesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect(Title::latest()->first())
        ->activations->toHaveCount(1)
        ->activations->first()->started_at->toDateTimeString()->toBe($activationDate);
});
