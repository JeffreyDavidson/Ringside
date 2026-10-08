<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousStables;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('PreviousStables query', function (): void {
    it('returns distinct previous stables associated through managed roster members in name order', function (): void {
        $manager = Manager::factory()->create();

        // Arrange
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $previousWrestlerStable = Stable::factory()->create(['name' => 'Alpha Stable']);
        $previousTagTeamStable = Stable::factory()->create(['name' => 'Beta Stable']);
        $nonOverlappingStable = Stable::factory()->create(['name' => 'Gamma Stable']);
        $currentStable = Stable::factory()->create(['name' => 'Current Stable']);

        $wrestler->managers()->attach($manager, [
            'hired_at' => Date::now()->subYears(3),
            'fired_at' => Date::now()->subYears(2),
        ]);
        $previousWrestlerStable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subYears(3)->addMonth(),
            'left_at' => Date::now()->subYears(2)->addMonth(),
        ]);
        $nonOverlappingStable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subYear(),
            'left_at' => Date::now()->subMonths(6),
        ]);

        $tagTeam->managers()->attach($manager, [
            'hired_at' => Date::now()->subYears(2),
            'fired_at' => Date::now()->subYear(),
        ]);
        $previousTagTeamStable->tagTeams()->attach($tagTeam, [
            'joined_at' => Date::now()->subYears(2)->addMonth(),
            'left_at' => Date::now()->subYear()->addMonth(),
        ]);
        $currentStable->tagTeams()->attach($tagTeam, [
            'joined_at' => Date::now()->subMonths(6),
        ]);
        $tagTeam->managers()->attach($manager, [
            'hired_at' => Date::now()->subMonths(5),
        ]);
        $table = new PreviousStables;
        $table->managerId = $manager->id;

        // Act
        $stables = $table->builder()->get();

        // Assert
        expect($stables->modelKeys())->toBe([
            $previousWrestlerStable->id,
            $previousTagTeamStable->id,
        ]);
    });

    it('includes associations whose membership and manager periods touch at an endpoint', function (): void {
        $manager = Manager::factory()->create();

        // Arrange
        $boundary = Date::parse('2024-01-01');
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $membershipBoundaryStable = Stable::factory()->create(['name' => 'Alpha Stable']);
        $assignmentBoundaryStable = Stable::factory()->create(['name' => 'Beta Stable']);

        $wrestler->managers()->attach($manager, [
            'hired_at' => $boundary,
            'fired_at' => $boundary->copy()->addMonth(),
        ]);
        $membershipBoundaryStable->wrestlers()->attach($wrestler, [
            'joined_at' => $boundary->copy()->subMonth(),
            'left_at' => $boundary,
        ]);

        $tagTeam->managers()->attach($manager, [
            'hired_at' => $boundary->copy()->subMonth(),
            'fired_at' => $boundary,
        ]);
        $assignmentBoundaryStable->tagTeams()->attach($tagTeam, [
            'joined_at' => $boundary,
            'left_at' => $boundary->copy()->addMonth(),
        ]);
        $table = new PreviousStables;
        $table->managerId = $manager->id;

        // Act
        $stables = $table->builder()->get();

        // Assert
        expect($stables->modelKeys())->toBe([
            $membershipBoundaryStable->id,
            $assignmentBoundaryStable->id,
        ]);
    });
});

describe('PreviousStables rendering', function (): void {
    it('renders stable names and search controls', function (): void {
        $manager = Manager::factory()->create();

        // Arrange
        $wrestler = Wrestler::factory()->create();
        $alphaStable = Stable::factory()->create(['name' => 'Alpha Stable']);
        $betaStable = Stable::factory()->create(['name' => 'Beta Stable']);
        $wrestler->managers()->attach($manager, [
            'hired_at' => Date::now()->subYears(2),
            'fired_at' => Date::now()->subYear(),
        ]);
        $alphaStable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subMonths(20),
            'left_at' => Date::now()->subMonths(18),
        ]);
        $betaStable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subMonths(16),
            'left_at' => Date::now()->subMonths(14),
        ]);

        // Act
        $table = livewire(PreviousStables::class, ['managerId' => $manager->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search stables"')
            ->assertSeeInOrder(['Alpha Stable', 'Beta Stable']);
    });

    it('searches previous stables by name', function (): void {
        $manager = Manager::factory()->create();

        // Arrange
        $wrestler = Wrestler::factory()->create();
        $alphaStable = Stable::factory()->create(['name' => 'Alpha Stable']);
        $betaStable = Stable::factory()->create(['name' => 'Beta Stable']);
        $wrestler->managers()->attach($manager, [
            'hired_at' => Date::now()->subYears(2),
            'fired_at' => Date::now()->subYear(),
        ]);
        $alphaStable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subMonths(20),
            'left_at' => Date::now()->subMonths(18),
        ]);
        $betaStable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subMonths(16),
            'left_at' => Date::now()->subMonths(14),
        ]);

        // Act
        $table = livewire(PreviousStables::class, ['managerId' => $manager->id]);
        $table->set('search', 'Alpha');

        // Assert
        $table
            ->assertSee('Alpha Stable')
            ->assertDontSee('Beta Stable');
    });
});
