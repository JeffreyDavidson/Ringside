<?php

test('updates a title and redirects', function () {
    $title = Title::factory()->create([
        'name' => 'Old Example Title',
    ]);
    $data = UpdateRequest::factory()->create([
        'name' => 'New Example Title',
        'start_date' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'edit'], $title))
        ->patch(action([TitlesController::class, 'update'], $title), $data)
        ->assertValid()
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($title->fresh())
        ->name->toBe('New Example Title')
        ->activations->toBeEmpty();
});

test('update can activate an unactivated title when activation date is filled', function () {
    $title = Title::factory()->unactivated()->create();
    $activationDate = now()->toDateTimeString();
    $data = UpdateRequest::factory()->create([
        'activation_date' => $activationDate,
    ]);

    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'edit'], $title))
        ->patch(action([TitlesController::class, 'update'], $title), $data)
        ->assertValid()
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect($title->fresh())
        ->activations->toHaveCount(1)
        ->activations->first()->started_at->toDateTimeString()->toBe($activationDate);
});

test('update can activate an inactive title', function () {
    $title = Title::factory()->inactive()->create();
    $activationDate = now()->toDateTimeString();
    $data = UpdateRequest::factory()->create([
        'activation_date' => $activationDate,
    ]);

    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'edit'], $title))
        ->patch(action([TitlesController::class, 'update'], $title), $data)
        ->assertValid()
        ->assertRedirect(action([TitlesController::class, 'index']));

    expect(Title::latest()->first())
        ->activations->toHaveCount(2)
        ->activations->last()->started_at->toDateTimeString()->toBe($activationDate);
});

test('update cannot activate an active title', function () {
    $title = Title::factory()->active()->create();
    $activationDate = now()->toDateTimeString();
    $data = UpdateRequest::factory()->create([
        'activation_date' => $activationDate,
    ]);

    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'edit'], $title))
        ->patch(action([TitlesController::class, 'update'], $title), $data)
        ->assertInvalid();
});
