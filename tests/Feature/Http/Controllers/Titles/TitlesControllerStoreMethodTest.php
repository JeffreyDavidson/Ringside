<?php

use App\Http\Controllers\Titles\TitlesController;
use App\Http\Requests\Titles\StoreRequest;
use App\Models\Title;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TitlesController::class, 'create']))
        ->assertViewIs('titles.create')
        ->assertViewHas('title', new Title);
});

test('a basic user cannot view the form for creating a title', function () {
    $this->actingAs(basicUser())
        ->get(action([TitlesController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a title', function () {
    $this->get(action([TitlesController::class, 'create']))
        ->assertRedirect(route('login'));
});

test('store creates a title and redirects', function () {
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

test('a basic user cannot create a title', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([TitlesController::class, 'store']), $data)
        ->assertForbidden();
});

test('a guest cannot create a title', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([TitlesController::class, 'store']), $data)
        ->assertRedirect(route('login'));
});
