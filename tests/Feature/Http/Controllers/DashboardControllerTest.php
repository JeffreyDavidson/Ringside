<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Http\Controllers\DashboardController;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
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
test('promotion members can view the dashboard', function () {
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);

    actingAs($user)
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

/**
 * @see DashboardController::__invoke()
 */
test('users without an active promotion membership see the no promotion page instead of data', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    Event::factory()->for($promotion, 'promotion')->create(['name' => 'Hidden Event', 'date' => now()->addDays(3)]);
    Wrestler::factory()->for($promotion, 'promotion')->employed()->create();

    // Act
    $response = actingAs(basicUser())
        ->get(route('dashboard'));

    // Assert
    $response
        ->assertForbidden()
        ->assertSee('You are not a member of a promotion yet')
        ->assertDontSee('Hidden Event')
        ->assertDontSee('Cleared to book');
});

/**
 * @see DashboardController::__invoke()
 */
test('members only see their own promotion on the dashboard', function () {
    // Arrange
    $mine = Promotion::factory()->create();
    $other = Promotion::factory()->create();
    $user = basicUser();
    $mine->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    Event::factory()->for($mine, 'promotion')->create(['name' => 'Own Event', 'date' => now()->addDays(3)]);
    Event::factory()->for($other, 'promotion')->create(['name' => 'Foreign Event', 'date' => now()->addDays(3)]);
    Wrestler::factory()->for($other, 'promotion')->employed()->count(5)->create();
    $foreignChampion = Wrestler::factory()->for($other, 'promotion')->employed()->create(['name' => 'Foreign Champion']);
    $foreignTitle = Title::factory()->for($other, 'promotion')->active()->create(['name' => 'Foreign Belt']);
    TitleChampionship::factory()->for($foreignTitle)->forWrestler($foreignChampion)->current()->create();

    // Act
    $response = actingAs($user)
        ->get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Own Event')
        ->assertDontSee('Foreign Event')
        ->assertDontSee('Foreign Belt')
        ->assertDontSee('Foreign Champion')
        ->assertSeeInOrder(['Cleared to book', '0', 'Injured', '0', 'Suspended', '0', 'Under contract', '0']);
});

/**
 * @see DashboardController::__invoke()
 */
test('administrators without a membership still see every promotion on the dashboard', function () {
    // Arrange
    Event::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'Platform Event', 'date' => now()->addDays(3)]);

    // Act
    $response = actingAs(administrator())
        ->get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Platform Event');
});
