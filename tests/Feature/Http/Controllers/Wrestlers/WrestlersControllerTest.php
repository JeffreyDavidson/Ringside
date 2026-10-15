<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Components\GeneralInfo;
use App\Livewire\Wrestlers\Components\Actions;
use App\Livewire\Wrestlers\Tables\Main;
use App\Livewire\Wrestlers\Tables\PreviousManagers;
use App\Livewire\Wrestlers\Tables\PreviousMatches;
use App\Livewire\Wrestlers\Tables\PreviousStables;
use App\Livewire\Wrestlers\Tables\PreviousTagTeams;
use App\Livewire\Wrestlers\Tables\PreviousTitleChampionships;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Suspension;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function (): void {
    test('an active promotion member receives the wrestlers index', function (): void {
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
        $response = get(route('wrestlers.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('wrestlers.index')
            ->assertSeeHtml('<title>Wrestlers · Ringside</title>')
            ->assertSeeLivewire(Main::class);
    });

    test('an administrator receives the wrestlers index view and table', function (): void {
        // Arrange
        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('wrestlers.index')
            ->assertSeeHtml('aria-label="Content workspace"')
            ->assertSeeLivewire(Main::class);
    });

    test('a guest cannot open the wrestlers index', function (): void {
        // Act
        $response = get(route('wrestlers.index'));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('a user without membership cannot open the wrestlers index', function (): void {
        // Arrange
        actingAs(basicUser());

        // Act
        $response = get(route('wrestlers.index'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });
});

describe('show', function (): void {
    test('renders the wrestler profile and its related data', function () {
        // Arrange
        $wrestler = Wrestler::factory()
            ->create(['name' => 'Marcus Vale']);

        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertSeeHtml('<title>Marcus Vale · Ringside</title>')
            ->assertSee($wrestler->name)
            ->assertSee('Status')
            ->assertViewHas('wrestler', $wrestler)
            ->assertSeeLivewire(Actions::class)
            ->assertSeeLivewire(GeneralInfo::class)
            ->assertSee($wrestler->status->label())
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousTitleChampionships::class)
            ->assertSeeLivewire(PreviousMatches::class)
            ->assertSeeLivewire(PreviousTagTeams::class)
            ->assertSeeLivewire(PreviousManagers::class)
            ->assertSeeLivewire(PreviousStables::class);
    });

    test('show labels an injured wrestler', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()
            ->employed()
            ->create();
        Injury::factory()
            ->for($wrestler, 'injurable')
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $wrestler)
            ->assertSee('Employed')
            ->assertSeeHtml('data-test="availability-injured"')
            ->assertDontSeeHtml('data-test="availability-suspended"');
    });

    test('show labels a suspended wrestler', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()
            ->employed()
            ->create();
        Suspension::factory()
            ->for($wrestler, 'suspendable')
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $wrestler)
            ->assertSee('Employed')
            ->assertDontSeeHtml('data-test="availability-injured"')
            ->assertSeeHtml('data-test="availability-suspended"');
    });

    test('show labels a wrestler who is both injured and suspended', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()
            ->employed()
            ->create();
        Injury::factory()
            ->for($wrestler, 'injurable')
            ->create();
        Suspension::factory()
            ->for($wrestler, 'suspendable')
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $wrestler)
            ->assertSee('Employed')
            ->assertSeeHtml('data-test="availability-injured"')
            ->assertSeeHtml('data-test="availability-suspended"');
    });

    test('show labels a healthy wrestler', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()
            ->employed()
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $wrestler)
            ->assertSee('Employed')
            ->assertDontSeeHtml('data-test="availability-injured"')
            ->assertDontSeeHtml('data-test="availability-suspended"');
    });

    test('show renders the related data displayed by the wrestler summary', function () {
        // Arrange
        Wrestler::factory()
            ->create();

        $wrestler = Wrestler::factory()
            ->employed()
            ->onCurrentTagTeam(TagTeam::factory()
                ->create(['name' => 'Tag Team Alpha']))
            ->create();
        $manager = Manager::factory()
            ->create([
                'first_name' => 'Travis',
                'last_name' => "O'Keefe",
            ]);
        $wrestler->managers()
            ->attach($manager, ['hired_at' => now()->subDay()]);
        TitleChampionship::factory()
            ->for(Title::factory()
                ->state(['name' => 'Heavyweight Belt']), 'title')
            ->forWrestler($wrestler)
            ->current()
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk()
            ->assertSee('Tag Team Alpha')
            ->assertSee($manager->full_name)
            ->assertSee('Heavyweight Belt')
            ->assertDontSeeHtml('&amp;#039;')
            ->assertSee($wrestler->employments()
                ->firstOrFail()->started_at->toDateString());
    });

    test('show loads the summary relationships only once', function () {
        // Arrange
        Wrestler::factory()
            ->create();

        $wrestler = Wrestler::factory()
            ->employed()
            ->create();
        $wrestler->managers()
            ->attach(Manager::factory()
                ->create(), ['hired_at' => now()->subDay()]);

        actingAs(administrator());

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk();

        $managerQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'wrestlers_managers'));
        DB::disableQueryLog();

        expect($managerQueries)->toHaveCount(1);
    });

    test('a user without a promotion membership cannot view wrestler profiles', function () {
        // Arrange
        $wrestler = Wrestler::factory()
            ->create();

        actingAs(basicUser());

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertForbidden();
    });

    test('a guest cannot view a wrestler profile', function () {
        // Arrange
        $wrestler = Wrestler::factory()
            ->create();

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('returns 404 when wrestler does not exist', function () {
        // Arrange
        Wrestler::factory()
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });

    test('an active promotion member receives the correct wrestler profile', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $wrestler = Wrestler::factory()
            ->for($promotion, 'promotion')
            ->create();

        $user = basicUser();
        $promotion->users()
            ->attach($user, [
                'role' => MembershipRole::Member,
                'status' => MembershipStatus::Active,
            ]);

        actingAs($user);

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('wrestlers.show')
            ->assertViewHas('wrestler', $wrestler);
    });
});
