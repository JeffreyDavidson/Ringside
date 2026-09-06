<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousStables;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->tagTeam = TagTeam::factory()->create();
    actingAs(administrator());
});

describe('PreviousStables configuration', function (): void {
    it('requires a tag team', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousStables())->builder())
            ->toThrow(LogicException::class, 'A tag team was not provided.');
    });
});

describe('PreviousStables query', function (): void {
    it('returns only ended stable memberships for the requested tag team in newest-first order', function (): void {
        // Arrange
        $otherTagTeam = TagTeam::factory()->create();
        $recentStable = Stable::factory()->create();
        $olderStable = Stable::factory()->create();
        $currentStable = Stable::factory()->create();
        $otherStable = Stable::factory()->create();
        $olderStable->tagTeams()->attach($this->tagTeam, [
            'joined_at' => Date::now()->subMonths(4),
            'left_at' => Date::now()->subMonths(3),
        ]);
        $recentStable->tagTeams()->attach($this->tagTeam, [
            'joined_at' => Date::now()->subMonths(2),
            'left_at' => Date::now()->subMonth(),
        ]);
        $currentStable->tagTeams()->attach($this->tagTeam, [
            'joined_at' => Date::now()->subWeek(),
        ]);
        $otherStable->tagTeams()->attach($otherTagTeam, [
            'joined_at' => Date::now()->subDays(3),
            'left_at' => Date::now()->subDay(),
        ]);
        $table = new PreviousStables();
        $table->tagTeamId = $this->tagTeam->id;

        // Act
        $stables = $table->builder()->get();

        // Assert
        expect($stables->modelKeys())->toBe([
            $recentStable->id,
            $olderStable->id,
        ]);
    });

    it('omits deleted stables', function (): void {
        // Arrange
        $stable = Stable::factory()->create();
        $stable->tagTeams()->attach($this->tagTeam, [
            'joined_at' => Date::now()->subMonth(),
            'left_at' => Date::now()->subWeek(),
        ]);
        $stable->delete();
        $table = new PreviousStables();
        $table->tagTeamId = $this->tagTeam->id;

        // Act
        $stables = $table->builder()->get();

        // Assert
        expect($stables)->toBeEmpty();
    });
});

describe('PreviousStables rendering', function (): void {
    it('renders previous stable names, dates, and search controls', function (): void {
        // Arrange
        $previousStable = Stable::factory()->create(['name' => 'Historic Stable']);
        $currentStable = Stable::factory()->create(['name' => 'Current Stable']);
        $joinedAt = Date::now()->subMonth();
        $leftAt = Date::now()->subWeek();
        $previousStable->tagTeams()->attach($this->tagTeam, [
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
        ]);
        $currentStable->tagTeams()->attach($this->tagTeam, [
            'joined_at' => Date::now()->subDay(),
        ]);

        // Act
        $component = livewire(PreviousStables::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search stables"')
            ->assertSee('Historic Stable')
            ->assertSee($joinedAt->format('Y-m-d'))
            ->assertSee($leftAt->format('Y-m-d'))
            ->assertDontSee('Current Stable');
    });

    it('searches previous stables by name', function (): void {
        // Arrange
        foreach (['Historic Stable', 'Former Stable'] as $offset => $name) {
            $stable = Stable::factory()->create(['name' => $name]);
            $stable->tagTeams()->attach($this->tagTeam, [
                'joined_at' => Date::now()->subMonths($offset + 3),
                'left_at' => Date::now()->subMonths($offset + 1),
            ]);
        }

        // Act
        $component = livewire(PreviousStables::class, ['tagTeamId' => $this->tagTeam->id]);
        $component->set('search', 'Historic');

        // Assert
        $component
            ->assertSee('Historic Stable')
            ->assertDontSee('Former Stable');
    });

    it('renders an empty state when the tag team has no previous stables', function (): void {
        // Act
        $component = livewire(PreviousStables::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousStables authorization', function (): void {
    it('allows administrators to view tag team stable history', function (): void {
        // Act
        $component = livewire(PreviousStables::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $component->assertSuccessful();
    });

    it('forbids users without access to the tag team', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $component = livewire(PreviousStables::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
