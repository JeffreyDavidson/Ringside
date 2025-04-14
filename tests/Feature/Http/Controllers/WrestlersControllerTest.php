<?php

declare(strict_types=1);

use App\Http\Controllers\WrestlersController;
use App\Livewire\Wrestlers\Tables\PreviousManagersTable;
use App\Livewire\Wrestlers\Tables\PreviousMatchesTable;
use App\Livewire\Wrestlers\Tables\PreviousStablesTable;
use App\Livewire\Wrestlers\Tables\PreviousTagTeamsTable;
use App\Livewire\Wrestlers\Tables\PreviousTitleChampionshipsTable;
use App\Livewire\Wrestlers\Tables\WrestlersTable;
use App\Models\User;
use App\Models\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function () {
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([WrestlersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('wrestlers.index')
            ->assertSeeLivewire(WrestlersTable::class);
    });

    test('a basic user cannot view wrestlers index page', function () {
        actingAs(basicUser())
            ->get(action([WrestlersController::class, 'index']))
            ->assertForbidden()
            ->assertDontSeeLivewire(WrestlersTable::class);
    });

    test('a guest cannot view wrestlers index page', function () {
        get(action([WrestlersController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->create();
    });

    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([WrestlersController::class, 'show'], $this->wrestler))
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $this->wrestler)
            ->assertSeeLivewire(PreviousTitleChampionshipsTable::class)
            ->assertSeeLivewire(PreviousMatchesTable::class)
            ->assertSeeLivewire(PreviousTagTeamsTable::class)
            ->assertSeeLivewire(PreviousManagersTable::class)
            ->assertSeeLivewire(PreviousStablesTable::class);
    });

    test('a basic user can view their wrestler profile', function () {
        $wrestler = Wrestler::factory()->for($user = basicUser())->create();

        actingAs($user)
            ->get(action([WrestlersController::class, 'show'], $wrestler))
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $wrestler);
    });

    test('a basic user cannot view another users wrestler profile', function () {
        $wrestler = Wrestler::factory()->for(User::factory())->create();

        actingAs(basicUser())
            ->get(action([WrestlersController::class, 'show'], $wrestler))
            ->assertForbidden();
    });

    test('a guest cannot view a wrestler profile', function () {
        get(action([WrestlersController::class, 'show'], $this->wrestler))
            ->assertRedirect(route('login'));
    });
});
