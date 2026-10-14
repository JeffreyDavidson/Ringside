<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Stables\Components\Actions;
use App\Livewire\Stables\Tables\Main;
use App\Livewire\Stables\Tables\PreviousManagers;
use App\Livewire\Stables\Tables\PreviousTagTeams;
use App\Livewire\Stables\Tables\PreviousWrestlers;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function (): void {
    test('an active promotion member receives the stables index', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = basicUser();
        $promotion->users()->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        actingAs($user);

        // Act
        $response = get(route('stables.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('stables.index')
            ->assertSeeLivewire(Main::class);
    });

    test('an administrator receives the stables index view and table', function (): void {
        // Arrange
        actingAs(administrator());

        // Act
        $response = get(route('stables.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('stables.index')
            ->assertSeeHtml('aria-label="Content workspace"')
            ->assertSeeLivewire(Main::class);
    });

    test('a guest cannot open the stables index', function (): void {
        // Act
        $response = get(route('stables.index'));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('a user without membership cannot open the stables index', function (): void {
        // Arrange
        actingAs(basicUser());

        // Act
        $response = get(route('stables.index'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });
});

describe('show', function (): void {
    test('renders the stable profile and its related data', function () {
        // Arrange
        $stable = Stable::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('stables.show', $stable));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('stables.show')
            ->assertSee($stable->name)
            ->assertSee('Status')
            ->assertViewHas('stable', $stable)
            ->assertSeeLivewire(Actions::class)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousTagTeams::class)
            ->assertSeeLivewire(PreviousManagers::class);
    });

    test('show leaves the summary relationships to the general info component', function () {
        // Arrange
        $stable = Stable::factory()->create();

        $startedAt = today()->subDay();
        ActivityPeriod::factory()
            ->for($stable, 'activeable')
            ->started($startedAt)
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('stables.show', $stable));

        // Assert
        $response
            ->assertOk()
            ->assertSee($startedAt->toDateString())
            ->assertSeeLivewire(GeneralInfo::class);
    });

    test('a user without a promotion membership cannot view stable profiles', function () {
        // Arrange
        $stable = Stable::factory()->create();

        actingAs(basicUser());

        // Act
        $response = get(route('stables.show', $stable));

        // Assert
        $response
            ->assertForbidden();
    });

    test('a guest cannot view a stable profile', function () {
        // Arrange
        $stable = Stable::factory()->create();

        // Act
        $response = get(route('stables.show', $stable));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('returns 404 when stable does not exist', function () {
        // Arrange
        Stable::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('stables.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });

    test('administrators can view stable profiles in every lifecycle state', function (string $state): void {
        // Arrange
        $stable = match ($state) {
            'active' => Stable::factory()->active()->create(),
            'inactive' => Stable::factory()->inactive()->create(),
            'retired' => Stable::factory()->retired()->create(),
            default => throw new LogicException('Unknown lifecycle fixture'),
        };

        actingAs(administrator());

        // Act
        $response = get(route('stables.show', $stable));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('stables.show')
            ->assertViewHas('stable', $stable);
    })->with([
        'active' => 'active',
        'inactive' => 'inactive',
        'retired' => 'retired',
    ]);

    test('an active promotion member receives the correct stable profile', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->for($promotion, 'promotion')->create();

        $user = basicUser();
        $promotion->users()->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        actingAs($user);

        // Act
        $response = get(route('stables.show', $stable));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('stables.show')
            ->assertViewHas('stable', $stable);
    });
});
