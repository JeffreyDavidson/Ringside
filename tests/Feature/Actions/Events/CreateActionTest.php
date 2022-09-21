<?php

test('store creates a event and redirects', function () {
    $data = StoreRequest::factory()->create([
        'name' => 'Example Event Name',
        'date' => null,
        'venue_id' => null,
        'preview' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([EventsController::class, 'create']))
        ->post(action([EventsController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([EventsController::class, 'index']));

    expect(Event::latest()->first())
        ->name->toBe('Example Event Name')
        ->date->toBeNull()
        ->venue_id->toBeNull()
        ->preview->toBeNull();
});
