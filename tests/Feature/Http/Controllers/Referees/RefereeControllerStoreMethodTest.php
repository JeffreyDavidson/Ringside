<?php

use App\Http\Controllers\Referees\RefereesController;
use App\Http\Requests\Referees\StoreRequest;
use App\Models\Referee;
use Illuminate\Support\Carbon;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([RefereesController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('referees.create')
        ->assertViewHas('referee', new Referee);
});

test('a basic user cannot view the form for creating a referee', function () {
    $this->actingAs(basicUser())
        ->get(action([RefereesController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a referee', function () {
    $this->get(action([RefereesController::class, 'create']))
        ->assertRedirect(route('login'));
});

test('store creates a referee and redirects', function () {
    $data = StoreRequest::factory()->create([
        'first_name' => 'Taylor',
        'last_name' => 'Otwell',
        'start_date' => null,
    ]);

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

test('a basic user cannot create a referee', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([RefereesController::class, 'store']), $data)
        ->assertForbidden();
});

test('a guest cannot create a referee', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([RefereesController::class, 'store']), $data)
        ->assertRedirect(route('login'));
});
