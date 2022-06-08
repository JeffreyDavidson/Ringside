<?php

use App\Http\Controllers\Managers\ManagersController;
use App\Http\Requests\Managers\StoreRequest;
use App\Models\Manager;
use Illuminate\Support\Carbon;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([ManagersController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('managers.create')
        ->assertViewHas('manager', new Manager);
});

test('a basic user cannot view the form for creating a manager', function () {
    $this->actingAs(basicUser())
        ->get(action([ManagersController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a manager', function () {
    $this->get(action([ManagersController::class, 'create']))
        ->assertRedirect(route('login'));
});

test('store creates a manager and redirects', function () {
    $data = StoreRequest::factory()->create([
        'first_name' => 'Taylor',
        'last_name' => 'Otwell',
        'started_at' => null,
    ]);

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

test('an employment is created for the manager if started at is filled in request', function () {
    $dateTime = Carbon::now()->toDateTimeString();
    $data = StoreRequest::factory()->create([
        'started_at' => $dateTime,
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

test('a basic user cannot create a manager', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([ManagersController::class, 'store']), $data)
        ->assertForbidden();
});

test('a guest cannot create a manager', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([ManagersController::class, 'store']), $data)
        ->assertRedirect(route('login'));
});
