<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\ViewModels\DashboardViewModel;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('promotion members can view the dashboard', function () {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

    $user = basicUser();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('dashboard')
        ->assertSeeHtml('<title>Overview · Ringside</title>');
});

test('a guest cannot view the dashboard', function () {
    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertRedirect(route('login'));
});

test('the dashboard shows the roster, upcoming events and current champions', function () {
    // Arrange
    $venue = Venue::factory()
        ->create(['name' => 'Riverside Armory']);
    $event = Event::factory()
        ->atVenue($venue)
        ->create([
            'name' => 'Winter Warfare',
            'date' => now()->addDays(14),
        ]);
    EventMatch::factory()
        ->forEvent($event)
        ->create();
    Wrestler::factory()
        ->employed()
        ->count(2)
        ->create();
    Wrestler::factory()
        ->injured()
        ->create();
    $champion = Wrestler::factory()
        ->employed()
        ->create(['name' => 'Marcus Vale']);
    $title = Title::factory()
        ->active()
        ->create(['name' => 'Heavyweight Championship']);
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler($champion)
        ->current()
        ->create();

    actingAs(administrator());

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('dashboard')
        ->assertViewHas('dashboard', fn (mixed $dashboard): bool => $dashboard instanceof DashboardViewModel)
        ->assertSeeInOrder(['Cleared to book', '3', 'Injured', '1', 'Suspended', '0', 'Under contract', '4'])
        ->assertSee('Winter Warfare')
        ->assertSee('Riverside Armory')
        ->assertSee('1 match booked')
        ->assertSeeHtml(route('events.show', $event))
        ->assertSee('Heavyweight Championship')
        ->assertSeeHtml(route('wrestlers.show', $champion));
});

test('the dashboard shows when a reign began as the day in the title promotion time zone', function () {
    // Arrange
    $promotion = Promotion::factory()
        ->create(['timezone' => 'America/Los_Angeles']);
    $title = Title::factory()
        ->active()
        ->for($promotion, 'promotion')
        ->create();
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler(Wrestler::factory()
            ->employed()
            ->create())
        ->current()
        ->create(['won_at' => Date::parse('2026-03-02 03:00:00', 'UTC')]);

    actingAs(administrator());

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Since Mar 1, 2026')
        ->assertDontSee('Mar 2, 2026');
});

test('the dashboard guides a new promotion when nothing is scheduled', function () {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertSee('No events are scheduled yet.')
        ->assertSee('No titles have a current champion yet.')
        ->assertSeeHtml(route('events.index'));
});

test('users without an active promotion membership see the no promotion page instead of data', function () {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    Event::factory()
        ->for($promotion, 'promotion')
        ->create([
            'name' => 'Hidden Event',
            'date' => now()->addDays(3),
        ]);
    Wrestler::factory()
        ->for($promotion, 'promotion')
        ->employed()
        ->create();

    actingAs(basicUser());

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertForbidden()
        ->assertSee('You are not a member of a promotion yet')
        ->assertDontSee('Hidden Event')
        ->assertDontSee('Cleared to book');
});

test('members only see their own promotion on the dashboard', function () {
    // Arrange
    [$ownPromotion, $foreignPromotion] = Promotion::factory()
        ->count(2)
        ->create()
        ->all();
    $user = basicUser();
    $ownPromotion->users()
        ->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);
    Event::factory()
        ->for($ownPromotion, 'promotion')
        ->create([
            'name' => 'Own Event',
            'date' => now()->addDays(3),
        ]);
    Event::factory()
        ->for($foreignPromotion, 'promotion')
        ->create([
            'name' => 'Foreign Event',
            'date' => now()->addDays(3),
        ]);
    Wrestler::factory()
        ->for($foreignPromotion, 'promotion')
        ->employed()
        ->count(5)
        ->create();
    $foreignChampion = Wrestler::factory()
        ->for($foreignPromotion, 'promotion')
        ->employed()
        ->create(['name' => 'Foreign Champion']);
    $foreignTitle = Title::factory()
        ->for($foreignPromotion, 'promotion')
        ->active()
        ->create(['name' => 'Foreign Belt']);
    TitleChampionship::factory()
        ->for($foreignTitle)
        ->forWrestler($foreignChampion)
        ->current()
        ->create();

    actingAs($user);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Own Event')
        ->assertDontSee('Foreign Event')
        ->assertDontSee('Foreign Belt')
        ->assertDontSee('Foreign Champion')
        ->assertSeeInOrder(['Cleared to book', '0', 'Injured', '0', 'Suspended', '0', 'Under contract', '0']);
});

test('administrators without a membership still see every promotion on the dashboard', function () {
    // Arrange
    Event::factory()
        ->for(Promotion::factory(), 'promotion')
        ->create([
            'name' => 'Platform Event',
            'date' => now()->addDays(3),
        ]);

    actingAs(administrator());

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Platform Event');
});

test('the dashboard pluralises the number of days a champion has held a title', function (int $days, string $expected) {
    // Arrange
    $title = Title::factory()
        ->active()
        ->create();
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler(Wrestler::factory()
            ->employed()
            ->create())
        ->current()
        ->create(['won_at' => now()->subDays($days)]);

    actingAs(administrator());

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertSee($expected);
})->with([
    'no days' => [0, '0 days'],
    'many days' => [12, '12 days'],
]);

test('the dashboard uses the singular for a one-day title reign', function (): void {
    // Arrange
    $title = Title::factory()
        ->active()
        ->create();
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler(Wrestler::factory()
            ->employed()
            ->create())
        ->current()
        ->create(['won_at' => now()->subDay()]);

    actingAs(administrator());

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response
        ->assertOk()
        ->assertSee('1 day')
        ->assertDontSee('1 days');
});

test('the dashboard issues the same number of queries however large the roster is', function () {
    // Arrange
    actingAs(administrator());
    Wrestler::factory()
        ->employed()
        ->count(5)
        ->create();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $baselineResponse = get(route('dashboard'));
    $queriesWithSmallRoster = count(DB::getQueryLog());
    DB::disableQueryLog();

    Wrestler::factory()
        ->employed()
        ->count(45)
        ->create();
    DB::flushQueryLog();
    DB::enableQueryLog();

    // Act
    $response = get(route('dashboard'));
    $queriesWithLargeRoster = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Assert
    $response
        ->assertOk();

    $baselineResponse
        ->assertOk();

    expect($queriesWithLargeRoster)->toBe($queriesWithSmallRoster);
});
