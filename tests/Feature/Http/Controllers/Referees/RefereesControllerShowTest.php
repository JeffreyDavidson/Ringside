<?php

declare(strict_types=1);

use App\Http\Controllers\Referees\RefereesController;
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
    beforeEach(function () {
        $this->referee = Referee::factory()->create();
    });

    /**
     * @see RefereesController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([RefereesController::class, 'show'], $this->referee))
            ->assertViewIs('referees.show')
            ->assertViewHas('referee', $this->referee)
            ->assertSeeLivewire(PreviousMatches::class);
    });

    /**
     * @see RefereesController::show()
     */
    test('show loads only the relationship rendered by the referee summary', function () {
        actingAs(administrator())
            ->get(action([RefereesController::class, 'show'], $this->referee))
            ->assertOk()
            ->assertViewHas('referee', fn (Referee $referee): bool => count($referee->getRelations()) === 1
                && $referee->relationLoaded('firstEmployment'));
    });

    /**
     * @see RefereesController::show()
     */
    test('a basic user cannot view a referee profile', function () {
        actingAs(basicUser())
            ->get(action([RefereesController::class, 'show'], $this->referee))
            ->assertForbidden();
    });

    /**
     * @see RefereesController::show()
     */
    test('a guest cannot view a referee profile', function () {
        get(action([RefereesController::class, 'show'], $this->referee))
            ->assertRedirect(route('login'));
    });

    /**
     * @see RefereesController::show()
     */
    test('an administrator can view referees in every lifecycle state', function () {
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
        actingAs(administrator())
            ->get(action([RefereesController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
