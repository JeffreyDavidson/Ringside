<?php

declare(strict_types=1);

use App\Http\Controllers\ManagersController;
use App\Livewire\Managers\Tables\ManagersTable;
use App\Models\Manager;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function () {
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([ManagersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('managers.index')
            ->assertSeeLivewire(ManagersTable::class);
    });

    test('a basic user cannot view managers index page', function () {
        actingAs(basicUser())
            ->get(action([ManagersController::class, 'index']))
            ->assertForbidden();
    });

    test('a guest cannot view managers index page', function () {
        get(action([ManagersController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->manager = Manager::factory()->create();
    });

    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([ManagersController::class, 'show'], $this->manager))
            ->assertViewIs('managers.show')
            ->assertViewHas('manager', $this->manager);
    });

    test('a basic user can view their manager profile', function () {
        $manager = Manager::factory()->for($user = basicUser())->create();

        actingAs($user)
            ->get(action([ManagersController::class, 'show'], $manager))
            ->assertOk();
    });

    test('a basic user cannot view another users manager profile', function () {
        $manager = Manager::factory()->for(User::factory())->create();

        actingAs(basicUser())
            ->get(action([ManagersController::class, 'show'], $manager))
            ->assertForbidden();
    });

    test('a guest cannot view a manager profile', function () {
        get(action([ManagersController::class, 'show'], $this->manager))
            ->assertRedirect(route('login'));
    });
});
