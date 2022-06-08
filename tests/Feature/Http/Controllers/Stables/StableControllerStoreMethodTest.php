<?php

use App\Http\Controllers\Stables\StablesController;
use App\Http\Requests\Stables\StoreRequest;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([StablesController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('stables.create')
        ->assertViewHas('stable', new Stable);
});

test('a basic user cannot view the form for creating a stable', function () {
    $this->actingAs(basicUser())
        ->get(action([StablesController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a stable', function () {
    $this->get(action([StablesController::class, 'create']))
        ->assertRedirect(route('login'));
});

test('store creates a stable and redirects', function () {
    $data = StoreRequest::factory()->create([
        'name' => 'Example Stable Name',
        'started_at' => null,
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

test('an activation is created for the stable if started at is filled in request', function () {
    $dateTime = now()->toDateTimeString();
    $wrestlers = Wrestler::factory()->count(3)->create();

    $data = StoreRequest::factory()->create([
        'started_at' => $dateTime,
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

test('a stables members join when stable is started if filled', function () {
    $dateTime = now()->toDateTimeString();
    $wrestlers = Wrestler::factory()->count(1)->create();
    $tagTeam = TagTeam::factory()->count(1)->create();

    $data = StoreRequest::factory()->create([
        'started_at' => $dateTime,
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

test('a stables members join at the current time when stable is created if started at is not filled', function () {
    $dateTime = now()->toDateTimeString();
    $wrestlers = Wrestler::factory()->count(1)->create();
    $tagTeam = TagTeam::factory()->count(1)->create();

    $data = StoreRequest::factory()->create([
        'started_at' => null,
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

test('a basic user cannot create a stable', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([StablesController::class, 'store']), $data)
        ->assertForbidden();
});

test('a guest cannot create a stable', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([StablesController::class, 'store']), $data)
        ->assertRedirect(route('login'));
});
