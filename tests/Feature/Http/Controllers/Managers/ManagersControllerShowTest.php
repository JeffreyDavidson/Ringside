<?php

declare(strict_types=1);

use App\Http\Controllers\Managers\ManagersController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Managers\Components\Actions;
use App\Livewire\Managers\Tables\PreviousStables;
use App\Livewire\Managers\Tables\PreviousTagTeams;
use App\Livewire\Managers\Tables\PreviousWrestlers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

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
            ->assertSee($this->manager->full_name)
            ->assertSee('Status')
            ->assertViewHas('manager', $this->manager)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousTagTeams::class)
            ->assertSeeLivewire(PreviousStables::class);
    });

    /**
     * @see ManagersController::show()
     */
    test('show renders the lifecycle actions component', function () {
        actingAs(administrator())
            ->get(route('managers.show', $this->manager))
            ->assertOk()
            ->assertSeeLivewire(Actions::class);
    });

    /**
     * @see ManagersController::show()
     */
    test('show renders the general info component', function () {
        actingAs(administrator())
            ->get(route('managers.show', $this->manager))
            ->assertOk()
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($this->manager->status->label());
    });

    /**
     * @see ManagersController::show()
     */
    test('show renders the related data displayed by the manager summary', function () {
        $manager = Manager::factory()->employed()->create();
        $wrestler = Wrestler::factory()->create(['name' => "Sean O'Neil"]);
        $tagTeam = TagTeam::factory()->create(['name' => 'Tag Team Alpha']);
        $manager->wrestlers()->attach($wrestler, ['hired_at' => now()->subDay()]);
        $manager->tagTeams()->attach($tagTeam, ['hired_at' => now()->subDay()]);

        actingAs(administrator())
            ->get(route('managers.show', $manager))
            ->assertOk()
            ->assertSee($wrestler->name)
            ->assertSee('Tag Team Alpha')
            ->assertSee($manager->employments()->firstOrFail()->started_at->toDateString());
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
