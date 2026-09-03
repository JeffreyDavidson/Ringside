<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousStables;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(User::factory()->administrator()->create());
});

it('requires a manager', function () {
    expect(fn () => (new PreviousStables())->builder())
        ->toThrow(LogicException::class, 'A manager was not provided.');
});

it('shows distinct previous stables associated through managed roster members', function () {
    $manager = Manager::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $previousWrestlerStable = Stable::factory()->create();
    $previousTagTeamStable = Stable::factory()->create();
    $nonOverlappingStable = Stable::factory()->create();
    $currentStable = Stable::factory()->create();

    $wrestler->managers()->attach($manager, [
        'hired_at' => now()->subYears(3),
        'fired_at' => now()->subYears(2),
    ]);
    $previousWrestlerStable->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subYears(3)->addMonth(),
        'left_at' => now()->subYears(2)->addMonth(),
    ]);
    $nonOverlappingStable->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subYear(),
        'left_at' => now()->subMonths(6),
    ]);

    $tagTeam->managers()->attach($manager, [
        'hired_at' => now()->subYears(2),
        'fired_at' => now()->subYear(),
    ]);
    $previousTagTeamStable->tagTeams()->attach($tagTeam, [
        'joined_at' => now()->subYears(2)->addMonth(),
        'left_at' => now()->subYear()->addMonth(),
    ]);
    $currentStable->tagTeams()->attach($tagTeam, [
        'joined_at' => now()->subMonths(6),
    ]);
    $tagTeam->managers()->attach($manager, [
        'hired_at' => now()->subMonths(5),
    ]);

    $stables = tap(app(PreviousStables::class), fn (PreviousStables $table) => $table->managerId = $manager->id)
        ->builder()
        ->get();

    expect($stables->modelKeys())->toEqualCanonicalizing([
        $previousWrestlerStable->id,
        $previousTagTeamStable->id,
    ]);
});

it('renders for an administrator', function () {
    $manager = Manager::factory()->create();

    livewire(PreviousStables::class, ['managerId' => $manager->id])
        ->assertSuccessful();
});
