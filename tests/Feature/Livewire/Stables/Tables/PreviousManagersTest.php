<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\PreviousManagers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->administrator()->create());
});

it('requires a stable', function () {
    expect(fn () => (new PreviousManagers())->builder())
        ->toThrow(LogicException::class, 'A stable was not provided.');
});

it('shows distinct previous managers associated through stable roster members', function () {
    $stable = Stable::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $previousWrestlerManager = Manager::factory()->create([
        'first_name' => 'Historic',
        'last_name' => 'Manager',
    ]);
    $previousTagTeamManager = Manager::factory()->create([
        'first_name' => 'Former',
        'last_name' => 'Advisor',
    ]);
    $nonOverlappingManager = Manager::factory()->create();
    $currentManager = Manager::factory()->create();

    $stable->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subYears(3),
        'left_at' => now()->subYear(),
    ]);
    $wrestler->managers()->attach($previousWrestlerManager, [
        'hired_at' => now()->subYears(2),
        'fired_at' => now()->subMonths(18),
    ]);
    $wrestler->managers()->attach($nonOverlappingManager, [
        'hired_at' => now()->subMonths(6),
        'fired_at' => now()->subMonths(3),
    ]);

    $stable->tagTeams()->attach($tagTeam, [
        'joined_at' => now()->subYears(2),
        'left_at' => now()->subMonths(6),
    ]);
    $tagTeam->managers()->attach($previousTagTeamManager, [
        'hired_at' => now()->subYear(),
        'fired_at' => now()->subMonths(9),
    ]);

    $currentWrestler = Wrestler::factory()->create();
    $stable->wrestlers()->attach($currentWrestler, [
        'joined_at' => now()->subMonths(3),
    ]);
    $currentWrestler->managers()->attach($currentManager, [
        'hired_at' => now()->subMonths(2),
    ]);

    $managers = tap(app(PreviousManagers::class), fn (PreviousManagers $table) => $table->stableId = $stable->id)
        ->builder()
        ->get();

    expect($managers->modelKeys())->toEqualCanonicalizing([
        $previousWrestlerManager->id,
        $previousTagTeamManager->id,
    ]);

    livewire(PreviousManagers::class, ['stableId' => $stable->id])
        ->set('search', 'Historic')
        ->assertSee('Historic Manager')
        ->assertDontSee('Former Advisor');
});

it('renders for an administrator', function () {
    $stable = Stable::factory()->create();

    livewire(PreviousManagers::class, ['stableId' => $stable->id])
        ->assertSuccessful();
});
