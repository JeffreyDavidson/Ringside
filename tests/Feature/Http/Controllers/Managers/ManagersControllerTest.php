<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Http\Controllers\Managers\ManagersController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Managers\Components\Actions;
use App\Livewire\Managers\Tables\Main;
use App\Livewire\Managers\Tables\PreviousStables;
use App\Livewire\Managers\Tables\PreviousTagTeams;
use App\Livewire\Managers\Tables\PreviousWrestlers;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Managers Controller.
 *
 * @see ManagersController
 */
describe('Managers Controller', function () {
    /**
     * @see ManagersController::show()
     */
    test('show returns a view', function () {
        // Arrange
        $manager = Manager::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('managers.show', $manager));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('managers.show')
            ->assertSee($manager->full_name)
            ->assertSee('Status')
            ->assertViewHas('manager', $manager)
            ->assertSeeLivewire(Actions::class)
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($manager->status->label())
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousTagTeams::class)
            ->assertSeeLivewire(PreviousStables::class);
    });

    /**
     * @see ManagersController::show()
     */
    test('show renders the related data displayed by the manager summary', function () {
        // Arrange
        Manager::factory()->create();

        $manager = Manager::factory()->employed()->create();
        $wrestler = Wrestler::factory()->create(['name' => "Sean O'Neil"]);
        $tagTeam = TagTeam::factory()->create(['name' => 'Tag Team Alpha']);
        $manager->wrestlers()->attach($wrestler, ['hired_at' => now()->subDay()]);
        $manager->tagTeams()->attach($tagTeam, ['hired_at' => now()->subDay()]);

        actingAs(administrator());

        // Act
        $response = get(route('managers.show', $manager));

        // Assert
        $response
            ->assertOk()
            ->assertSee($wrestler->name)
            ->assertSee('Tag Team Alpha')
            ->assertSee($manager->employments()->firstOrFail()->started_at->toDateString());
    });

    /**
     * @see ManagersController::show()
     */
    test('a user without a promotion membership cannot view manager profiles', function () {
        // Arrange
        $manager = Manager::factory()->create();

        actingAs(basicUser());

        // Act
        $response = get(route('managers.show', $manager));

        // Assert
        $response
            ->assertForbidden();
    });

    /**
     * @see ManagersController::show()
     */
    test('a guest cannot view a manager profile', function () {
        // Arrange
        $manager = Manager::factory()->create();

        // Act
        $response = get(route('managers.show', $manager));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    /**
     * @see ManagersController::show()
     */
    test('an administrator can view managers in every lifecycle state', function (string $state): void {
        // Arrange
        $manager = match ($state) {
            'employed' => Manager::factory()->employed()->create(),
            'injured' => Manager::factory()->injured()->create(),
            'retired' => Manager::factory()->retired()->create(),
            'suspended' => Manager::factory()->suspended()->create(),
            default => throw new LogicException('Unknown lifecycle fixture'),
        };
        actingAs(administrator());

        // Act
        $response = get(route('managers.show', $manager));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('managers.show')
            ->assertViewHas('manager', $manager);
    })->with(['employed', 'injured', 'retired', 'suspended']);

    /**
     * @see ManagersController::show()
     */
    test('returns 404 when manager does not exist', function () {
        // Arrange
        Manager::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('managers.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });
});

test('an administrator receives the managers index view and table', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('managers.index'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('managers.index')
        ->assertSeeHtml('aria-label="Content workspace"')
        ->assertSeeLivewire(Main::class);
});

test('a guest cannot open the managers index', function (): void {
    // Act
    $response = get(route('managers.index'));

    // Assert
    $response->assertRedirect(route('login'));
});

test('a user without membership cannot open the managers index', function (): void {
    // Arrange
    actingAs(basicUser());

    // Act
    $response = get(route('managers.index'));

    // Assert
    $response
        ->assertForbidden()
        ->assertViewIs('promotions.no-membership');
});

test('an active promotion member receives the correct manager profile', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $record = Manager::factory()->for($promotion, 'promotion')->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('managers.show', $record));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('managers.show')
        ->assertViewHas('manager', $record);
});
