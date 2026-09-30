<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\ViewModels\DashboardViewModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for DashboardController.
 *
 * @see DashboardController
 */

/**
 * @see DashboardController::__invoke()
 */
test('administrators can view the dashboard', function () {
    actingAs(administrator())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard');
});

/**
 * @see DashboardController::__invoke()
 */
test('basic users can view the dashboard', function () {
    actingAs(basicUser())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard');
});

/**
 * @see DashboardController::__invoke()
 */
test('a guest cannot view the dashboard', function () {
    get(route('dashboard'))
        ->assertRedirect(route('login'));
});

/**
 * @see DashboardController::__invoke()
 */
test('the dashboard shows the roster, upcoming events and current champions', function () {
    // Arrange
    $venue = Venue::factory()->create(['name' => 'Riverside Armory']);
    $event = Event::factory()->atVenue($venue)->create(['name' => 'Winter Warfare', 'date' => now()->addDays(14)]);
    EventMatch::factory()->forEvent($event)->create();
    Wrestler::factory()->employed()->count(2)->create();
    Wrestler::factory()->injured()->create();
    $champion = Wrestler::factory()->employed()->create(['name' => 'Marcus Vale']);
    $title = Title::factory()->active()->create(['name' => 'Heavyweight Championship']);
    TitleChampionship::factory()->for($title)->forWrestler($champion)->current()->create();

    // Act
    $response = actingAs(administrator())
        ->get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertViewHas('dashboard', fn (mixed $dashboard): bool => $dashboard instanceof DashboardViewModel)
        ->assertSeeInOrder(['Cleared to book', '3', 'Injured', '1', 'Suspended', '0', 'Under contract', '4'])
        ->assertSee('Winter Warfare')
        ->assertSee('Riverside Armory')
        ->assertSee('1 match booked')
        ->assertSeeHtml(route('events.show', $event))
        ->assertSee('Heavyweight Championship')
        ->assertSeeHtml(route('wrestlers.show', $champion));
});

/**
 * @see DashboardController::__invoke()
 */
test('the dashboard guides a new promotion when nothing is scheduled', function () {
    // Act
    $response = actingAs(administrator())
        ->get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertSee('No events are scheduled yet.')
        ->assertSee('No titles have a current champion yet.')
        ->assertSeeHtml(route('events.index'));
});
