<?php

declare(strict_types=1);

use App\Http\Controllers\Referees\RefereesController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Referees\Components\Actions;
use App\Livewire\Referees\Tables\PreviousMatches;
use App\Models\Roster\Referees\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Referees Controller.
 *
 * @see RefereesController
 */
describe('Referees Controller', function () {
    /**
     * @see RefereesController::show()
     */
    test('show returns a view', function () {
        $referee = Referee::factory()->create();

        actingAs(administrator())
            ->get(route('referees.show', $referee))
            ->assertViewIs('referees.show')
            ->assertSee($referee->full_name)
            ->assertSee('Status')
            ->assertViewHas('referee', $referee)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousMatches::class);
    });

    /**
     * @see RefereesController::show()
     */
    test('show renders the lifecycle actions component', function () {
        $referee = Referee::factory()->create();

        actingAs(administrator())
            ->get(route('referees.show', $referee))
            ->assertOk()
            ->assertSeeLivewire(Actions::class);
    });

    /**
     * @see RefereesController::show()
     */
    test('show renders the general info component', function () {
        $referee = Referee::factory()->create();

        actingAs(administrator())
            ->get(route('referees.show', $referee))
            ->assertOk()
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($referee->status->label());
    });

    /**
     * @see RefereesController::show()
     */
    test('show renders the start date displayed by the referee summary', function () {
        Referee::factory()->create();

        $referee = Referee::factory()->employed()->create();

        actingAs(administrator())
            ->get(route('referees.show', $referee))
            ->assertOk()
            ->assertSee($referee->employments()->firstOrFail()->started_at->toDateString());
    });

    /**
     * @see RefereesController::show()
     */
    test('a basic user cannot view a referee profile', function () {
        $referee = Referee::factory()->create();

        actingAs(basicUser())
            ->get(route('referees.show', $referee))
            ->assertForbidden();
    });

    /**
     * @see RefereesController::show()
     */
    test('a guest cannot view a referee profile', function () {
        $referee = Referee::factory()->create();

        get(route('referees.show', $referee))
            ->assertRedirect(route('login'));
    });

    /**
     * @see RefereesController::show()
     */
    test('an administrator can view referees in every lifecycle state', function () {
        Referee::factory()->create();

        // Arrange
        $administrator = administrator();
        $referees = [
            Referee::factory()->bookable()->create(),
            Referee::factory()->injured()->create(),
            Referee::factory()->retired()->create(),
            Referee::factory()->suspended()->create(),
        ];

        // Act
        $responses = [];
        foreach ($referees as $referee) {
            $responses[] = actingAs($administrator)
                ->get(route('referees.show', $referee));
        }

        // Assert
        foreach ($responses as $response) {
            $response->assertSuccessful();
        }
    });

    /**
     * @see RefereesController::show()
     */
    test('returns 404 when referee does not exist', function () {
        Referee::factory()->create();

        actingAs(administrator())
            ->get(route('referees.show', 999999))
            ->assertNotFound();
    });
});
