<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\PreviousManagers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->stable = Stable::factory()->create();
    actingAs(administrator());
});

describe('PreviousManagers configuration', function (): void {
    it('requires a stable', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousManagers())->builder())
            ->toThrow(LogicException::class, 'A stable was not provided.');
    });
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
        $table = new PreviousManagers();
        $table->stableId = $this->stable->id;

        // Act
        $managers = $table->builder()->get();

        // Assert
        expect($managers->modelKeys())->toBe([
            $previousTagTeamManager->id,
            $previousWrestlerManager->id,
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

    it('renders an empty state when the stable has no previous managers', function (): void {
        // Act
        $table = livewire(PreviousManagers::class, ['stableId' => $this->stable->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousManagers authorization', function (): void {
    it('allows administrators to view stable manager history', function (): void {
        // Act
        $table = livewire(PreviousManagers::class, ['stableId' => $this->stable->id]);

        // Assert
        $table->assertSuccessful();
    });

    it('forbids users without access to the stable', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire(PreviousManagers::class, ['stableId' => $this->stable->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
