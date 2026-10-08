<?php

declare(strict_types=1);

use App\Livewire\Events\Tables\Main as EventsTable;
use App\Livewire\Managers\Tables\Main as ManagersTable;
use App\Livewire\Promotions\Tables\Main as PromotionsTable;
use App\Livewire\Referees\Tables\Main as RefereesTable;
use App\Livewire\Stables\Tables\Main as StablesTable;
use App\Livewire\TagTeams\Tables\Main as TagTeamsTable;
use App\Livewire\Titles\Tables\Main as TitlesTable;
use App\Livewire\Users\Tables\Main as UsersTable;
use App\Livewire\Venues\Tables\Main as VenuesTable;
use App\Livewire\Wrestlers\Tables\Main as WrestlersTable;
use Illuminate\Contracts\View\View;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
 * Every index page is served by the index action of its resource controller and renders the table's Livewire
 * component inside the content workspace. The cases give the route name, the view and the table component.
 */
dataset('index pages', [
    'events' => ['events.index', 'events.index', EventsTable::class],
    'managers' => ['managers.index', 'managers.index', ManagersTable::class],
    'promotions' => ['promotions.index', 'promotions.index', PromotionsTable::class],
    'referees' => ['referees.index', 'referees.index', RefereesTable::class],
    'stables' => ['stables.index', 'stables.index', StablesTable::class],
    'tag teams' => ['tag-teams.index', 'tag-teams.index', TagTeamsTable::class],
    'titles' => ['titles.index', 'titles.index', TitlesTable::class],
    'users' => ['users.index', 'users.index', UsersTable::class],
    'venues' => ['venues.index', 'venues.index', VenuesTable::class],
    'wrestlers' => ['wrestlers.index', 'wrestlers.index', WrestlersTable::class],
]);

test('an administrator can view the index page with its table component', function (string $route, string $view, string $table) {
    // Arrange
    $administrator = administrator();

    // Act
    $response = actingAs($administrator)
        ->get(route($route));

    // Assert
    $response
        ->assertOk()
        ->assertSeeHtml('aria-label="Content workspace"')
        ->assertSeeLivewire($table);
    expect($response->original)->toBeInstanceOf(View::class)
        ->name()->toBe($view);
})->with('index pages');

test('a basic user cannot view the index page', function (string $route) {
    // Arrange
    $basicUser = basicUser();

    // Act
    $response = actingAs($basicUser)
        ->get(route($route));

    // Assert
    $response->assertForbidden();
})->with('index pages');

test('a guest is redirected to login before reaching the index authorization policy', function (string $route) {
    // Act
    $response = get(route($route));

    // Assert
    $response->assertRedirect(route('login'));
})->with('index pages');
