<?php

declare(strict_types=1);

use App\Http\Controllers\Managers\ManagersController;
use App\Livewire\Managers\Tables\PreviousTagTeams;
use App\Livewire\Managers\Tables\PreviousWrestlers;
use App\Models\Managers\Manager;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Managers Controller.
 *
 * @see ManagersController
 */
describe('Managers Controller', function () {
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
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousTagTeams::class);
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
