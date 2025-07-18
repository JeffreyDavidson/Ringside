<?php

declare(strict_types=1);

use App\Http\Controllers\Managers\ShowController;
use App\Livewire\Managers\Tables\PreviousTagTeamsTable;
use App\Livewire\Managers\Tables\PreviousWrestlersTable;
use App\Models\Managers\Manager;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Managers Show Controller.
 *
 * @see ShowController
 */
describe('Managers Show Controller', function () {
    beforeEach(function () {
        $this->manager = Manager::factory()->create();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, $this->manager))
            ->assertOk()
            ->assertViewIs('managers.show')
            ->assertViewHas('manager', $this->manager)
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousMain::class);
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user cannot view manager profiles', function () {
        actingAs(basicUser())
            ->get(action(ShowController::class, $this->manager))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a guest cannot view a manager profile', function () {
        get(action(ShowController::class, $this->manager))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ShowController::__invoke()
     */
    test('returns 404 when manager does not exist', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, 999999))
            ->assertNotFound();
    });
});
