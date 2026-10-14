<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Venues\Tables\Main;
use App\Livewire\Venues\Tables\PreviousEvents;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function (): void {
    test('an active promotion member cannot open the platform venue directory', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = basicUser();
        $promotion->users()->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        actingAs($user);

        // Act
        $response = get(route('venues.index'));

        // Assert
        $response
            ->assertForbidden();
    });

    test('an administrator receives the venues index view and table', function (): void {
        // Arrange
        actingAs(administrator());

        // Act
        $response = get(route('venues.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('venues.index')
            ->assertSeeHtml('aria-label="Content workspace"')
            ->assertSeeLivewire(Main::class);
    });

    test('a guest cannot open the venues index', function (): void {
        // Act
        $response = get(route('venues.index'));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('a user without membership cannot open the venues index', function (): void {
        // Arrange
        actingAs(basicUser());

        // Act
        $response = get(route('venues.index'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });
});

describe('show', function (): void {
    test('renders the venue profile and its related data', function () {
        // Arrange
        $venue = Venue::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('venues.show', $venue));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('venues.show')
            ->assertSee($venue->name)
            ->assertSee('Address')
            ->assertViewHas('venue', $venue)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousEvents::class);
    });

    test('a user without a promotion membership cannot view a venue', function () {
        // Arrange
        $venue = Venue::factory()->create();

        actingAs(basicUser());

        // Act
        $response = get(route('venues.show', $venue));

        // Assert
        $response
            ->assertForbidden();
    });

    test('a guest cannot view a venue', function () {
        // Arrange
        $venue = Venue::factory()->create();

        // Act
        $response = get(route('venues.show', $venue));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('returns 404 when venue does not exist', function () {
        // Arrange
        Venue::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('venues.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });

    test('an active promotion member cannot open the platform venue profile', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $venue = Venue::factory()->create();

        $user = basicUser();
        $promotion->users()->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        actingAs($user);

        // Act
        $response = get(route('venues.show', $venue));

        // Assert
        $response
            ->assertForbidden();
    });
});
