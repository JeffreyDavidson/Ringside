<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\TagTeams\Components\Actions;
use App\Livewire\TagTeams\Tables\Main;
use App\Livewire\TagTeams\Tables\PreviousManagers;
use App\Livewire\TagTeams\Tables\PreviousMatches;
use App\Livewire\TagTeams\Tables\PreviousStables;
use App\Livewire\TagTeams\Tables\PreviousTitleChampionships;
use App\Livewire\TagTeams\Tables\PreviousWrestlers;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for TagTeams Controller.
 *
 * @see TagTeamsController
 */
describe('TagTeams Controller', function () {
    /**
     * @see TagTeamsController::show()
     */
    test('show returns a view', function () {
        // Arrange
        $tagTeam = TagTeam::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('tag-teams.show', $tagTeam));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('tag-teams.show')
            ->assertSee($tagTeam->name)
            ->assertSee('Status')
            ->assertViewHas('tagTeam', $tagTeam)
            ->assertSeeLivewire(Actions::class)
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($tagTeam->status->label())
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousTitleChampionships::class)
            ->assertSeeLivewire(PreviousMatches::class)
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousManagers::class)
            ->assertSeeLivewire(PreviousStables::class);
    });

    /**
     * @see TagTeamsController::show()
     */
    test('show renders the related data displayed by the tag team summary', function () {
        // Arrange
        TagTeam::factory()->create();

        $tagTeam = TagTeam::factory()->employed()->create();
        $tagTeam->currentWrestlers->firstOrFail()->update(['name' => "O'Neil & Sons"]);
        $tagTeam->currentWrestlers->skip(1)->firstOrFail()->update(['name' => "D'Angelo & Sons"]);
        $manager = Manager::factory()->create(['first_name' => 'Travis', 'last_name' => "O'Keefe"]);
        $tagTeam->managers()->attach($manager, ['hired_at' => now()->subDay()]);
        TitleChampionship::factory()
            ->for(Title::factory()->state(['name' => 'Tag Team Belt']), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('tag-teams.show', $tagTeam));

        // Assert
        $response
            ->assertOk()
            ->assertSee($manager->full_name)
            ->assertSee('Tag Team Belt');

        $tagTeam->currentWrestlers->each(fn (Wrestler $wrestler) => $response->assertSee($wrestler->name));
        $response->assertDontSeeHtml('&amp;#039;');
    });

    /**
     * @see TagTeamsController::show()
     */
    test('a user without a promotion membership cannot view tag team profiles', function () {
        // Arrange
        $tagTeam = TagTeam::factory()->create();

        actingAs(basicUser());

        // Act
        $response = get(route('tag-teams.show', $tagTeam));

        // Assert
        $response
            ->assertForbidden();
    });

    /**
     * @see TagTeamsController::show()
     */
    test('a guest cannot view a tag team profile', function () {
        // Arrange
        TagTeam::factory()->create();

        $tagTeam = TagTeam::factory()->create();

        // Act
        $response = get(route('tag-teams.show', $tagTeam));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    /**
     * @see TagTeamsController::show()
     */
    test('returns 404 when tag team does not exist', function () {
        // Arrange
        TagTeam::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('tag-teams.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });
});

test('an administrator receives the tag-teams index view and table', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('tag-teams.index'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('tag-teams.index')
        ->assertSeeHtml('aria-label="Content workspace"')
        ->assertSeeLivewire(Main::class);
});

test('a guest cannot open the tag-teams index', function (): void {
    // Act
    $response = get(route('tag-teams.index'));

    // Assert
    $response->assertRedirect(route('login'));
});

test('a user without membership cannot open the tag-teams index', function (): void {
    // Arrange
    actingAs(basicUser());

    // Act
    $response = get(route('tag-teams.index'));

    // Assert
    $response
        ->assertForbidden()
        ->assertViewIs('promotions.no-membership');
});

test('an active promotion member receives the correct tagTeam profile', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $record = TagTeam::factory()->for($promotion, 'promotion')->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('tag-teams.show', $record));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('tag-teams.show')
        ->assertViewHas('tagTeam', $record);
});
