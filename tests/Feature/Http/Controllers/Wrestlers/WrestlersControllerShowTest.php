<?php

declare(strict_types=1);

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Livewire\Wrestlers\Tables\PreviousManagers;
use App\Livewire\Wrestlers\Tables\PreviousMatches;
use App\Livewire\Wrestlers\Tables\PreviousStables;
use App\Livewire\Wrestlers\Tables\PreviousTagTeams;
use App\Livewire\Wrestlers\Tables\PreviousTitleChampionships;
use App\Models\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Wrestlers Controller.
 *
 * @see WrestlersController
 */
describe('Wrestlers Controller', function () {
    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->create();
    });

    /**
     * @see WrestlersController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([WrestlersController::class, 'show'], $this->wrestler))
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $this->wrestler)
            ->assertSeeLivewire(PreviousTitleChampionships::class)
            ->assertSeeLivewire(PreviousMatches::class)
            ->assertSeeLivewire(PreviousTagTeams::class)
            ->assertSeeLivewire(PreviousManagers::class)
            ->assertSeeLivewire(PreviousStables::class);
    });

    /**
     * @see WrestlersController::show()
     */
    test('a basic user cannot view wrestler profiles', function () {
        actingAs(basicUser())
            ->get(action([WrestlersController::class, 'show'], $this->wrestler))
            ->assertForbidden();
    });

    /**
     * @see WrestlersController::show()
     */
    test('a guest cannot view a wrestler profile', function () {
        get(action([WrestlersController::class, 'show'], $this->wrestler))
            ->assertRedirect(route('login'));
    });

    /**
     * @see WrestlersController::show()
     */
    test('returns 404 when wrestler does not exist', function () {
        actingAs(administrator())
            ->get(action([WrestlersController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
