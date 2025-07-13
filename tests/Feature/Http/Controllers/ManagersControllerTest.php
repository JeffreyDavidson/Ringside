<?php

declare(strict_types=1);

use App\Http\Controllers\ManagersController;
use App\Livewire\Managers\Tables\ManagersTable;
use App\Livewire\Managers\Tables\PreviousTagTeamsTable;
use App\Livewire\Managers\Tables\PreviousWrestlersTable;
use App\Models\Managers\Manager;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for ManagersController.
 *
 * @see ManagersController
 */
describe('index', function () {
    /**
     * @see ManagersController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([ManagersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('managers.index')
            ->assertSeeLivewire(ManagersTable::class);
    });

    /**
     * @see ManagersController::index()
     */
    test('a basic user cannot view managers index page', function () {
        actingAs(basicUser())
            ->get(action([ManagersController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see ManagersController::index()
     */
    test('a guest cannot view managers index page', function () {
        get(action([ManagersController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->manager = Manager::factory()->create();
    });

    /**
     * @see ManagersController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([ManagersController::class, 'show'], $this->manager))
            ->assertOk()
            ->assertViewIs('managers.show')
            ->assertViewHas('manager', $this->manager)
            ->assertSeeLivewire(PreviousWrestlersTable::class)
            ->assertSeeLivewire(PreviousTagTeamsTable::class);
    });

    /**
     * @see ManagersController::show()
     */
    test('a basic user cannot view manager profiles', function () {
        actingAs(basicUser())
            ->get(action([ManagersController::class, 'show'], $this->manager))
            ->assertForbidden();
    });

    /**
     * @see ManagersController::show()
     */
    test('a guest cannot view a manager profile', function () {
        get(action([ManagersController::class, 'show'], $this->manager))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ManagersController::show()
     */
    test('returns 404 when manager does not exist', function () {
        actingAs(administrator())
            ->get(action([ManagersController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
