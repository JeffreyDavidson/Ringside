<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\PreviousManagers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->stable = Stable::factory()->create();
    actingAs(administrator());
});

describe('PreviousManagers query', function (): void {
    it('returns distinct previous managers associated through stable roster members in name order', function (): void {
        // Arrange
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

        $this->stable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subYears(3),
            'left_at' => Date::now()->subYear(),
        ]);
        $wrestler->managers()->attach($previousWrestlerManager, [
            'hired_at' => Date::now()->subYears(2),
            'fired_at' => Date::now()->subMonths(18),
        ]);
        $wrestler->managers()->attach($nonOverlappingManager, [
            'hired_at' => Date::now()->subMonths(6),
            'fired_at' => Date::now()->subMonths(3),
        ]);

        $this->stable->tagTeams()->attach($tagTeam, [
            'joined_at' => Date::now()->subYears(2),
            'left_at' => Date::now()->subMonths(6),
        ]);
        $tagTeam->managers()->attach($previousTagTeamManager, [
            'hired_at' => Date::now()->subYear(),
            'fired_at' => Date::now()->subMonths(9),
        ]);

        $currentWrestler = Wrestler::factory()->create();
        $this->stable->wrestlers()->attach($currentWrestler, [
            'joined_at' => Date::now()->subMonths(3),
        ]);
        $currentWrestler->managers()->attach($currentManager, [
            'hired_at' => Date::now()->subMonths(2),
        ]);
        $table = new PreviousManagers;
        $table->stableId = $this->stable->id;

        // Act
        $managers = $table->builder()->get();

        // Assert
        expect($managers->modelKeys())->toBe([
            $previousTagTeamManager->id,
            $previousWrestlerManager->id,
        ]);
    });

    it('includes associations whose membership and manager periods touch at an endpoint', function (): void {
        // Arrange
        $boundary = Date::parse('2024-01-01');
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $membershipBoundaryManager = Manager::factory()->create();
        $assignmentBoundaryManager = Manager::factory()->create();

        $this->stable->wrestlers()->attach($wrestler, [
            'joined_at' => $boundary->copy()->subMonth(),
            'left_at' => $boundary,
        ]);
        $wrestler->managers()->attach($membershipBoundaryManager, [
            'hired_at' => $boundary,
            'fired_at' => $boundary->copy()->addMonth(),
        ]);

        $this->stable->tagTeams()->attach($tagTeam, [
            'joined_at' => $boundary,
            'left_at' => $boundary->copy()->addMonth(),
        ]);
        $tagTeam->managers()->attach($assignmentBoundaryManager, [
            'hired_at' => $boundary->copy()->subMonth(),
            'fired_at' => $boundary,
        ]);
        $table = new PreviousManagers;
        $table->stableId = $this->stable->id;

        // Act
        $managers = $table->builder()->get();

        // Assert
        expect($managers->modelKeys())->toEqualCanonicalizing([
            $membershipBoundaryManager->id,
            $assignmentBoundaryManager->id,
        ]);
    });
});

describe('PreviousManagers rendering', function (): void {
    it('renders manager names, statuses, and search controls', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $historicManager = Manager::factory()->create([
            'first_name' => 'Historic',
            'last_name' => 'Manager',
        ]);
        $formerManager = Manager::factory()->create([
            'first_name' => 'Former',
            'last_name' => 'Advisor',
        ]);
        $this->stable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subYears(2),
            'left_at' => Date::now()->subYear(),
        ]);
        $wrestler->managers()->attach($historicManager, [
            'hired_at' => Date::now()->subMonths(18),
            'fired_at' => Date::now()->subMonths(15),
        ]);
        $wrestler->managers()->attach($formerManager, [
            'hired_at' => Date::now()->subMonths(14),
            'fired_at' => Date::now()->subMonths(12),
        ]);

        // Act
        $table = livewire(PreviousManagers::class, ['stableId' => $this->stable->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search managers"')
            ->assertSee('Historic Manager')
            ->assertSee($historicManager->status->label())
            ->assertSee('Former Advisor');
    });

    it('searches previous managers by name', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $historicManager = Manager::factory()->create([
            'first_name' => 'Historic',
            'last_name' => 'Manager',
        ]);
        $formerManager = Manager::factory()->create([
            'first_name' => 'Former',
            'last_name' => 'Advisor',
        ]);
        $this->stable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subYears(2),
            'left_at' => Date::now()->subYear(),
        ]);
        $wrestler->managers()->attach($historicManager, [
            'hired_at' => Date::now()->subMonths(18),
            'fired_at' => Date::now()->subMonths(15),
        ]);
        $wrestler->managers()->attach($formerManager, [
            'hired_at' => Date::now()->subMonths(14),
            'fired_at' => Date::now()->subMonths(12),
        ]);

        // Act
        $table = livewire(PreviousManagers::class, ['stableId' => $this->stable->id]);
        $table->set('search', 'Historic');

        // Assert
        $table
            ->assertSee('Historic Manager')
            ->assertDontSee('Former Advisor');
    });
});

describe('PreviousManagers query count', function (): void {
    it('runs the same number of queries regardless of how many managers it lists', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $this->stable->wrestlers()->attach($wrestler, [
            'joined_at' => Date::now()->subYears(2),
            'left_at' => Date::now()->subYear(),
        ]);
        $attachManagers = function (int $count) use ($wrestler): void {
            Manager::factory()->employed()->count($count)->create()->each(
                fn (Manager $manager) => $wrestler->managers()->attach($manager, [
                    'hired_at' => Date::now()->subMonths(18),
                    'fired_at' => Date::now()->subMonths(15),
                ])
            );
        };
        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            livewire(PreviousManagers::class, ['stableId' => $this->stable->id])->assertSee('Employed');
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };
        $attachManagers(1);
        $queriesWithOneManager = $countQueries();

        // Act
        $attachManagers(9);
        $queriesWithTenManagers = $countQueries();

        // Assert
        expect($queriesWithTenManagers)->toBe($queriesWithOneManager);
    });
});
