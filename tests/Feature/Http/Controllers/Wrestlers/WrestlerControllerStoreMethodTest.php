<?php

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\StoreRequest;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([WrestlersController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('wrestlers.create')
        ->assertViewHas('wrestler', new Wrestler);
});

test('a basic user cannot view the form for creating a wrestler', function () {
    $this->actingAs(basicUser())
        ->get(action([WrestlersController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a wrestler', function () {
    $this->get(action([WrestlersController::class, 'create']))
        ->assertRedirect(route('login'));
});

test('store creates a wrestler and redirects', function () {
    $data = StoreRequest::factory()->create([
        'name' => 'Example Wrestler Name',
        'feet' => 6,
        'inches' => 10,
        'weight' => 300,
        'hometown' => 'Laraville, New York',
        'signature_move' => null,
        'started_at' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([WrestlersController::class, 'create']))
        ->post(action([WrestlersController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect(Wrestler::latest()->first())
        ->name->toBe('Example Wrestler Name')
        ->height->toBe(82)
        ->weight->toBe(300)
        ->hometown->toBe('Laraville, New York')
        ->signature_move->toBeNull()
        ->employments->toBeEmpty();
});

test('store creates a wrestler with a signature move and redirects', function () {
    $data = StoreRequest::factory()->create([
        'signature_move' => 'Example Finishing Move',
    ]);

    $this->actingAs(administrator())
        ->from(action([WrestlersController::class, 'create']))
        ->post(action([WrestlersController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect(Wrestler::latest()->first())
        ->signature_move->toBe('Example Finishing Move');
});

test('an employment is created for the wrestler if started at is filled in request', function () {
    $dateTime = Carbon::now()->toDateTimeString();
    $data = StoreRequest::factory()->create([
        'started_at' => $dateTime,
    ]);

    $this->actingAs(administrator())
        ->from(action([WrestlersController::class, 'create']))
        ->post(action([WrestlersController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect(Wrestler::latest()->first())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($dateTime);
});

test('a basic user cannot create a wrestler', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([WrestlersController::class, 'store']), $data)
        ->assertForbidden();
});

test('a guest cannot create a wrestler', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([WrestlersController::class, 'store']), $data)
        ->assertRedirect(route('login'));
});
