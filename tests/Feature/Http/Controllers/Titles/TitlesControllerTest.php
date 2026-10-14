<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Titles\Components\Actions;
use App\Livewire\Titles\Tables\Main;
use App\Livewire\Titles\Tables\TitleHistory;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function (): void {
    test('an active promotion member receives the titles index', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = basicUser();
        $promotion->users()->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        actingAs($user);

        // Act
        $response = get(route('titles.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('titles.index')
            ->assertSeeLivewire(Main::class);
    });

    test('an administrator receives the titles index view and table', function (): void {
        // Arrange
        actingAs(administrator());

        // Act
        $response = get(route('titles.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('titles.index')
            ->assertSeeHtml('aria-label="Content workspace"')
            ->assertSeeLivewire(Main::class);
    });

    test('a guest cannot open the titles index', function (): void {
        // Act
        $response = get(route('titles.index'));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('a user without membership cannot open the titles index', function (): void {
        // Arrange
        actingAs(basicUser());

        // Act
        $response = get(route('titles.index'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });
});

describe('show', function (): void {
    test('renders the title profile and its related data', function () {
        // Arrange
        $title = Title::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('titles.show', $title));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('titles.show')
            ->assertSee($title->name)
            ->assertSee('Current Champion')
            ->assertSee('Vacant')
            ->assertViewHas('title', $title)
            ->assertSeeLivewire(Actions::class)
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($title->status->label())
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(TitleHistory::class);
    });

    test('show renders the related data displayed by the title summary', function () {
        // Arrange
        $title = Title::factory()->create();

        $startedAt = today()->subDay();
        ActivityPeriod::factory()
            ->for($title, 'activeable')
            ->started($startedAt)
            ->create();
        $wrestler = Wrestler::factory()->create();
        TitleChampionship::factory()->for($title, 'title')->forWrestler($wrestler)->current()->create();

        actingAs(administrator());

        // Act
        $response = get(route('titles.show', $title));

        // Assert
        $response
            ->assertOk()
            ->assertSee($startedAt->toDateString())
            ->assertSee($wrestler->name);
    });

    test('a user without a promotion membership cannot view a title', function () {
        // Arrange
        $title = Title::factory()->create();

        actingAs(basicUser());

        // Act
        $response = get(route('titles.show', $title));

        // Assert
        $response
            ->assertForbidden();
    });

    test('a guest cannot view a title', function () {
        // Arrange
        $title = Title::factory()->create();

        // Act
        $response = get(route('titles.show', $title));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('returns 404 when title does not exist', function () {
        // Arrange
        Title::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('titles.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });

    test('an active promotion member receives the correct title profile', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $title = Title::factory()->for($promotion, 'promotion')->create();

        $user = basicUser();
        $promotion->users()->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        actingAs($user);

        // Act
        $response = get(route('titles.show', $title));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('titles.show')
            ->assertViewHas('title', $title);
    });
});
