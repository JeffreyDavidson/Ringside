<?php

use App\Http\Controllers\Managers\ManagersController;
use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;

test('edit returns a view', function () {
    $manager = Manager::factory()->create();

    $this->actingAs(administrator())
        ->get(action([ManagersController::class, 'edit'], $manager))
        ->assertStatus(200)
        ->assertViewIs('managers.edit')
        ->assertViewHas('manager', $manager);
});

test('a basic user cannot view the form for editing a manager', function () {
    $manager = Manager::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([ManagersController::class, 'edit'], $manager))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a manager', function () {
    $manager = Manager::factory()->create();

    $this->get(action([ManagersController::class, 'edit'], $manager))
        ->assertRedirect(route('login'));
});

test('updates a manager and redirects', function () {
    $manager = Manager::factory()->create([
        'first_name' => 'Dries',
        'last_name' => 'Vints',
    ]);

    $data = UpdateRequest::factory()->create([
        'first_name' => 'Taylor',
        'last_name' => 'Otwell',
        'started_at' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([ManagersController::class, 'edit'], $manager))
        ->patch(action([ManagersController::class, 'update'], $manager), $data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh()->first_name)->toBe('Taylor');
    expect($manager->fresh()->last_name)->toBe('Otwell');

    expect($manager->fresh()->employments)->toBeEmpty();
});

test('update can employ an unemployed manager when started at is filled', function () {
    $now = now();
    $manager = Manager::factory()->unemployed()->create();
    $data = UpdateRequest::factory()->create(['started_at' => $now->toDateTimeString()]);

    $this->actingAs(administrator())
        ->from(action([ManagersController::class, 'edit'], $manager))
        ->patch(action([ManagersController::class, 'update'], $manager), $data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh()->employments)->toHaveCount(1);
    expect($manager->fresh()->employments->first()->started_at->toDateTimeString())->toBe($now->toDateTimeString());
});

test('update can employ a future employed manager when started at is filled', function () {
    $now = now();
    $manager = Manager::factory()->withFutureEmployment()->create();
    $data = UpdateRequest::factory()->create(['started_at' => $now->toDateTimeString()]);

    $this->actingAs(administrator())
        ->from(action([ManagersController::class, 'edit'], $manager))
        ->patch(action([ManagersController::class, 'update'], $manager), $data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh()->employments)->toHaveCount(1);
    expect($manager->fresh()->employments->first()->started_at->toDateTimeString())->toBe($now->toDateTimeString());
});

test('updating cannot employ an available manager when started at is filled', function () {
    $now = now();
    $manager = Manager::factory()->available()->create();
    $data = UpdateRequest::factory()->create(['started_at' => $now->toDateTimeString()]);

    $this->actingAs(administrator())
        ->from(action([ManagersController::class, 'edit'], $manager))
        ->patch(action([ManagersController::class, 'update'], $manager), $data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh()->employments)->toHaveCount(1);
});

test('a basic user cannot update a manager', function () {
    $manager = Manager::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->actingAs(basicUser())
        ->patch(action([ManagersController::class, 'update'], $manager), $data)
        ->assertForbidden();
});

test('a guest cannot update a manager', function () {
    $manager = Manager::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->patch(action([ManagersController::class, 'update'], $manager), $data)
        ->assertRedirect(route('login'));
});
