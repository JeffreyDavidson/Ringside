<?php

test('store creates a stable and redirects', function () {
    $data = StoreRequest::factory()->create([
        'name' => 'Example Stable Name',
        'start_date' => null,
        'wrestlers' => [],
        'tag_teams' => [],
    ]);

    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'create']))
        ->post(action([StablesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([StablesController::class, 'index']));

    expect(Stable::latest()->first())
        ->name->toBe('Example Stable Name')
        ->activations->toBeEmpty()
        ->wrestlers->toBeEmpty()
        ->tagteams->toBeEmpty();
});

test('an activation is created for the stable if start date is filled in request', function () {
    testTime()->freeze();
    $dateTime = now()->toDateTimeString();
    $wrestlers = Wrestler::factory()->count(3)->create();

    $data = StoreRequest::factory()->create([
        'start_date' => $dateTime,
        'wrestlers' => $wrestlers->modelKeys(),
    ]);

    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'create']))
        ->post(action([StablesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([StablesController::class, 'index']));

    expect(Stable::latest()->first())
        ->activations->toHaveCount(1)
        ->activations->last()->started_at->toDateTimeString()->toBe($dateTime);
});

test('wrestlers are added to stable if present', function () {
    $wrestlers = Wrestler::factory()->count(3)->create();

    $data = StoreRequest::factory()->create([
        'wrestlers' => $wrestlers->modelKeys(),
    ]);

    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'create']))
        ->post(action([StablesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([StablesController::class, 'index']));

    expect(Stable::latest()->first())
        ->currentWrestlers->toHaveCount(3)
        ->currentWrestlers->modelKeys()->toEqual($wrestlers->modelKeys());
});

test('tag teams are added to stable if present', function () {
    $tagTeams = TagTeam::factory()->count(2)->create();

    $data = StoreRequest::factory()->create([
        'tag_teams' => $tagTeams->modelKeys(),
    ]);

    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'create']))
        ->post(action([StablesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([StablesController::class, 'index']));

    expect(Stable::latest()->first())
        ->currentTagTeams->toHaveCount(2)
        ->currentTagTeams->modelKeys()->toEqual($tagTeams->modelKeys());
});

test('a stables members join when start date is filled', function () {
    testTime()->freeze();
    $dateTime = now()->toDateTimeString();
    $wrestlers = Wrestler::factory()->count(1)->create();
    $tagTeam = TagTeam::factory()->count(1)->create();

    $data = StoreRequest::factory()->create([
        'start_date' => $dateTime,
        'wrestlers' => $wrestlers->modelKeys(),
        'tag_teams' => $tagTeam->modelKeys(),
    ]);

    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'create']))
        ->post(action([StablesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([StablesController::class, 'index']));

    expect(Stable::latest()->first())
        ->currentWrestlers->each(fn ($wrestler) => $wrestler->pivot->joined_at->toDateTimeString()->toBe($dateTime))
        ->currentTagTeams->each(fn ($tagTeam) => $tagTeam->pivot->joined_at->toDateTimeString()->toBe($dateTime));
});

test('a stables members join at the current time when stable is created if start date is not filled', function () {
    testTime()->freeze();
    $dateTime = now()->toDateTimeString();
    $wrestlers = Wrestler::factory()->count(1)->create();
    $tagTeam = TagTeam::factory()->count(1)->create();

    $data = StoreRequest::factory()->create([
        'start_date' => null,
        'wrestlers' => $wrestlers->modelKeys(),
        'tag_teams' => $tagTeam->modelKeys(),
    ]);

    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'create']))
        ->post(action([StablesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([StablesController::class, 'index']));

    expect(Stable::latest()->first())
        ->currentWrestlers->each(fn ($wrestler) => $wrestler->pivot->joined_at->toDateTimeString()->toBe($dateTime))
        ->currentTagTeams->each(fn ($tagTeam) => $tagTeam->pivot->joined_at->toDateTimeString()->toBe($dateTime));
});
