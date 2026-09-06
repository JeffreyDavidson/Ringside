<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousStables;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->manager = Manager::factory()->create();
    actingAs(administrator());
});

describe('PreviousStables configuration', function (): void {
    it('requires a manager', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousStables())->builder())
            ->toThrow(LogicException::class, 'A manager was not provided.');
    });
});

describe('PreviousStables query', function (): void {
    it('returns distinct previous stables associated through managed roster members in name order', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $previousWrestlerStable = Stable::factory()->create(['name' => 'Alpha Stable']);
        $previousTagTeamStable = Stable::factory()->create(['name' => 'Beta Stable']);
        $nonOverlappingStable = Stable::factory()->create(['name' => 'Gamma Stable']);
        $currentStable = Stable::factory()->create(['name' => 'Current Stable']);

        $wrestler->managers()->attach($this->manager, [
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

        $tagTeam->managers()->attach($this->manager, [
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
        $tagTeam->managers()->attach($this->manager, [
            'hired_at' => Date::now()->subMonths(5),
        ]);
        $table = new PreviousStables();
        $table->managerId = $this->manager->id;

        // Act
        $stables = $table->builder()->get();

        // Assert
        expect($stables->modelKeys())->toBe([
            $previousWrestlerStable->id,
            $previousTagTeamStable->id,
        ]);
    });
});

describe('PreviousStables rendering', function (): void {
    it('renders stable names and search controls', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $alphaStable = Stable::factory()->create(['name' => 'Alpha Stable']);
        $betaStable = Stable::factory()->create(['name' => 'Beta Stable']);
        $wrestler->managers()->attach($this->manager, [
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
        $table = livewire(PreviousStables::class, ['managerId' => $this->manager->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search stables"')
            ->assertSeeInOrder(['Alpha Stable', 'Beta Stable']);
    });

    it('searches previous stables by name', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $alphaStable = Stable::factory()->create(['name' => 'Alpha Stable']);
        $betaStable = Stable::factory()->create(['name' => 'Beta Stable']);
        $wrestler->managers()->attach($this->manager, [
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
        $table = livewire(PreviousStables::class, ['managerId' => $this->manager->id]);
        $table->set('search', 'Alpha');

        // Assert
        $table
            ->assertSee('Alpha Stable')
            ->assertDontSee('Beta Stable');
    });

    it('renders an empty state when the manager has no previous stables', function (): void {
        // Act
        $table = livewire(PreviousStables::class, ['managerId' => $this->manager->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousStables authorization', function (): void {
    it('allows administrators to view manager stable history', function (): void {
        // Act
        $table = livewire(PreviousStables::class, ['managerId' => $this->manager->id]);

        // Assert
        $table->assertSuccessful();
    });

    it('forbids users without access to the manager', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire(PreviousStables::class, ['managerId' => $this->manager->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
