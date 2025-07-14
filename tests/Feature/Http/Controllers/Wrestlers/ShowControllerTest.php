<?php

declare(strict_types=1);

use App\Http\Controllers\Wrestlers\ShowController;
use App\Livewire\Wrestlers\Tables\PreviousManagersTable;
use App\Livewire\Wrestlers\Tables\PreviousMatchesTable;
use App\Livewire\Wrestlers\Tables\PreviousStablesTable;
use App\Livewire\Wrestlers\Tables\PreviousTagTeamsTable;
use App\Livewire\Wrestlers\Tables\PreviousTitleChampionshipsTable;
use App\Models\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Wrestlers Show Controller.
 *
 * @see ShowController
 */
describe('Wrestlers Show Controller', function () {
    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->create();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, $this->wrestler))
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $this->wrestler)
            ->assertSeeLivewire(PreviousTitleChampionshipsTable::class)
            ->assertSeeLivewire(PreviousMatchesTable::class)
            ->assertSeeLivewire(PreviousTagTeamsTable::class)
            ->assertSeeLivewire(PreviousManagersTable::class)
            ->assertSeeLivewire(PreviousStablesTable::class);
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user cannot view wrestler profiles', function () {
        actingAs(basicUser())
            ->get(action(ShowController::class, $this->wrestler))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a guest cannot view a wrestler profile', function () {
        get(action(ShowController::class, $this->wrestler))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ShowController::__invoke()
     */
    test('returns 404 when wrestler does not exist', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, 999999))
            ->assertNotFound();
    });
});