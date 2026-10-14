<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Http\Controllers\Referees\RefereesController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Referees\Components\Actions;
use App\Livewire\Referees\Tables\Main;
use App\Livewire\Referees\Tables\PreviousMatches;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Referees Controller.
 *
 * @see RefereesController
 */
describe('Referees Controller', function () {
    /**
     * @see RefereesController::show()
     */
    test('show returns a view', function () {
        // Arrange
        $referee = Referee::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('referees.show', $referee));

        // Assert
        $response
            ->assertViewIs('referees.show')
            ->assertSee($referee->full_name)
            ->assertSee('Status')
            ->assertViewHas('referee', $referee)
            ->assertSeeLivewire(Actions::class)
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($referee->status->label())
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousMatches::class);
    });

    /**
     * @see RefereesController::show()
     */
    test('show renders the start date displayed by the referee summary', function () {
        // Arrange
        Referee::factory()->create();

        $referee = Referee::factory()->employed()->create();

        actingAs(administrator());

        // Act
        $response = get(route('referees.show', $referee));

        // Assert
        $response
            ->assertOk()
            ->assertSee($referee->employments()->firstOrFail()->started_at->toDateString());
    });

    /**
     * @see RefereesController::show()
     */
    test('a user without a promotion membership cannot view a referee profile', function () {
        // Arrange
        $referee = Referee::factory()->create();

        actingAs(basicUser());

        // Act
        $response = get(route('referees.show', $referee));

        // Assert
        $response
            ->assertForbidden();
    });

    /**
     * @see RefereesController::show()
     */
    test('a guest cannot view a referee profile', function () {
        // Arrange
        $referee = Referee::factory()->create();

        // Act
        $response = get(route('referees.show', $referee));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    /**
     * @see RefereesController::show()
     */
    test('an administrator can view referees in every lifecycle state', function (string $state): void {
        // Arrange
        $referee = match ($state) {
            'bookable' => Referee::factory()->bookable()->create(),
            'injured' => Referee::factory()->injured()->create(),
            'retired' => Referee::factory()->retired()->create(),
            'suspended' => Referee::factory()->suspended()->create(),
            default => throw new LogicException('Unknown lifecycle fixture'),
        };
        actingAs(administrator());

        // Act
        $response = get(route('referees.show', $referee));

        // Assert
        $response->assertOk()->assertViewIs('referees.show')->assertViewHas('referee', $referee);
    })->with(['bookable', 'injured', 'retired', 'suspended']);

    /**
     * @see RefereesController::show()
     */
    test('returns 404 when referee does not exist', function () {
        // Arrange
        Referee::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('referees.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });
});

test('an administrator receives the referees index view and table', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('referees.index'));

    // Assert
    $response->assertOk()->assertViewIs('referees.index')
        ->assertSeeHtml('aria-label="Content workspace"')
        ->assertSeeLivewire(Main::class);
});

test('a guest cannot open the referees index', function (): void {
    // Act
    $response = get(route('referees.index'));

    // Assert
    $response->assertRedirect(route('login'));
});

test('a user without membership cannot open the referees index', function (): void {
    // Arrange
    actingAs(basicUser());

    // Act
    $response = get(route('referees.index'));

    // Assert
    $response->assertForbidden()->assertViewIs('promotions.no-membership');
});

test('an active promotion member receives the correct referee profile', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $record = Referee::factory()->for($promotion, 'promotion')->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('referees.show', $record));

    // Assert
    $response->assertOk()->assertViewIs('referees.show')->assertViewHas('referee', $record);
});
