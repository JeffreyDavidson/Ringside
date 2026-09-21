<?php

declare(strict_types=1);

use App\Http\Controllers\Managers\ManagersController;
use App\Livewire\Managers\Tables\PreviousStables;
use App\Livewire\Managers\Tables\PreviousTagTeams;
use App\Livewire\Managers\Tables\PreviousWrestlers;
use App\Models\Roster\Managers\Manager;

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
            ->get(route('managers.show', $this->manager))
            ->assertOk()
            ->assertViewIs('managers.show')
            ->assertViewHas('manager', $this->manager)
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousTagTeams::class)
            ->assertSeeLivewire(PreviousStables::class);
    });

    /**
     * @see ManagersController::show()
     */
    test('show loads only the relationships rendered by the manager summary', function () {
        actingAs(administrator())
            ->get(route('managers.show', $this->manager))
            ->assertOk()
            ->assertViewHas('manager', fn (Manager $manager): bool => count($manager->getRelations()) === 3
                && $manager->relationLoaded('currentTagTeams')
                && $manager->relationLoaded('currentWrestlers')
                && $manager->relationLoaded('firstEmployment'));
    });

    /**
     * @see ManagersController::show()
     */
    test('a basic user cannot view manager profiles', function () {
        actingAs(basicUser())
            ->get(route('managers.show', $this->manager))
            ->assertForbidden();
    });

    /**
     * @see ManagersController::show()
     */
    test('a guest cannot view a manager profile', function () {
        get(route('managers.show', $this->manager))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ManagersController::show()
     */
    test('an administrator can view managers in every lifecycle state', function () {
        // Arrange
        $administrator = administrator();
        $managers = [
            Manager::factory()->employed()->create(),
            Manager::factory()->injured()->create(),
            Manager::factory()->retired()->create(),
            Manager::factory()->suspended()->create(),
        ];

        // Act
        $responses = [];
        foreach ($managers as $manager) {
            $responses[] = actingAs($administrator)
                ->get(route('managers.show', $manager));
        }

        // Assert
        foreach ($responses as $response) {
            $response->assertSuccessful();
        }
    });

    /**
     * @see ManagersController::show()
     */
    test('returns 404 when manager does not exist', function () {
        actingAs(administrator())
            ->get(route('managers.show', 999999))
            ->assertNotFound();
    });
});
