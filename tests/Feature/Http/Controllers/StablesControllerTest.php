<?php

declare(strict_types=1);

use App\Http\Controllers\StablesController;
use App\Livewire\Stables\Tables\PreviousManagersTable;
use App\Livewire\Stables\Tables\PreviousTagTeamsTable;
use App\Livewire\Stables\Tables\PreviousWrestlersTable;
use App\Livewire\Stables\Tables\StablesTable;
use App\Models\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function () {
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([StablesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('stables.index')
            ->assertSeeLivewire(StablesTable::class);
    });

    test('a basic user cannot view stables index page', function () {
        actingAs(basicUser())
            ->get(action([StablesController::class, 'index']))
            ->assertForbidden();
    });

    test('a guest cannot view stables index page', function () {
        get(action([StablesController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->stable = Stable::factory()->create();
    });

    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([StablesController::class, 'show'], $this->stable))
            ->assertViewIs('stables.show')
            ->assertViewHas('stable', $this->stable)
            ->assertSeeLivewire(PreviousWrestlersTable::class)
            ->assertSeeLivewire(PreviousTagTeamsTable::class)
            ->assertSeeLivewire(PreviousManagersTable::class);
    });

    test('a basic user can view their stable profile', function () {
        $stable = Stable::factory()->for($user = basicUser())->create();

        actingAs($user)
            ->get(action([StablesController::class, 'show'], $stable))
            ->assertOk();
    });
});
