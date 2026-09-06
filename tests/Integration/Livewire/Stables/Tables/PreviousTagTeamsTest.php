<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\PreviousTagTeams;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->stable = Stable::factory()->create();
    actingAs(administrator());
});

describe('PreviousTagTeams configuration', function (): void {
    it('requires a stable', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousTagTeams())->builder())
            ->toThrow(LogicException::class, 'A stable was not provided.');
    });
});

describe('PreviousTagTeams query', function (): void {
    it('returns only ended tag team memberships for the requested stable in newest-first order', function (): void {
        // Arrange
        $otherStable = Stable::factory()->create();
        $recentTagTeam = TagTeam::factory()->create();
        $olderTagTeam = TagTeam::factory()->create();
        $currentTagTeam = TagTeam::factory()->create();
        $otherTagTeam = TagTeam::factory()->create();

        StableTagTeam::query()->create([
            'stable_id' => $this->stable->id,
            'tag_team_id' => $olderTagTeam->id,
            'joined_at' => Date::now()->subMonths(4),
            'left_at' => Date::now()->subMonths(3),
        ]);
        StableTagTeam::query()->create([
            'stable_id' => $this->stable->id,
            'tag_team_id' => $recentTagTeam->id,
            'joined_at' => Date::now()->subMonths(2),
            'left_at' => Date::now()->subMonth(),
        ]);
        StableTagTeam::query()->create([
            'stable_id' => $this->stable->id,
            'tag_team_id' => $currentTagTeam->id,
            'joined_at' => Date::now()->subWeek(),
            'left_at' => null,
        ]);
        StableTagTeam::query()->create([
            'stable_id' => $otherStable->id,
            'tag_team_id' => $otherTagTeam->id,
            'joined_at' => Date::now()->subDays(3),
            'left_at' => Date::now()->subDay(),
        ]);

        $table = new PreviousTagTeams();
        $table->stableId = $this->stable->id;

        // Act
        $memberships = $table->builder()->get();

        // Assert
        expect($memberships->pluck('tag_team_id')->all())->toBe([
            $recentTagTeam->id,
            $olderTagTeam->id,
        ])->and($memberships->every->relationLoaded('tagTeam'))->toBeTrue();
    });
});

describe('PreviousTagTeams rendering', function (): void {
    it('renders previous tag team links, membership dates, and search controls', function (): void {
        // Arrange
        $formerTagTeam = TagTeam::factory()->create(['name' => 'Former Tag Team']);
        $currentTagTeam = TagTeam::factory()->create(['name' => 'Current Tag Team']);
        $joinedAt = Date::now()->subMonths(3);
        $leftAt = Date::now()->subMonth();

        StableTagTeam::query()->create([
            'stable_id' => $this->stable->id,
            'tag_team_id' => $formerTagTeam->id,
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
        ]);
        StableTagTeam::query()->create([
            'stable_id' => $this->stable->id,
            'tag_team_id' => $currentTagTeam->id,
            'joined_at' => Date::now()->subWeek(),
            'left_at' => null,
        ]);

        // Act
        $table = livewire(PreviousTagTeams::class, ['stableId' => $this->stable->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search tag teams"')
            ->assertSee('Former Tag Team')
            ->assertSee(route('tag-teams.show', $formerTagTeam))
            ->assertSee($joinedAt->format('Y-m-d'))
            ->assertSee($leftAt->format('Y-m-d'))
            ->assertDontSee('Current Tag Team');
    });

    it('searches previous tag teams by name', function (): void {
        // Arrange
        foreach (['Historic Tag Team', 'Former Tag Team'] as $offset => $name) {
            $tagTeam = TagTeam::factory()->create(['name' => $name]);
            StableTagTeam::query()->create([
                'stable_id' => $this->stable->id,
                'tag_team_id' => $tagTeam->id,
                'joined_at' => Date::now()->subMonths($offset + 3),
                'left_at' => Date::now()->subMonths($offset + 1),
            ]);
        }

        // Act
        $table = livewire(PreviousTagTeams::class, ['stableId' => $this->stable->id]);
        $table->set('search', 'Historic');

        // Assert
        $table
            ->assertSee('Historic Tag Team')
            ->assertDontSee('Former Tag Team');
    });

    it('renders an unknown tag team when the related tag team was deleted', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->create();
        StableTagTeam::query()->create([
            'stable_id' => $this->stable->id,
            'tag_team_id' => $tagTeam->id,
            'joined_at' => Date::now()->subMonth(),
            'left_at' => Date::now()->subWeek(),
        ]);
        $tagTeam->delete();

        // Act
        $table = livewire(PreviousTagTeams::class, ['stableId' => $this->stable->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('Unknown');
    });

    it('renders an empty state when the stable has no previous tag teams', function (): void {
        // Act
        $table = livewire(PreviousTagTeams::class, ['stableId' => $this->stable->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousTagTeams authorization', function (): void {
    it('allows administrators to view stable tag team history', function (): void {
        // Act
        $table = livewire(PreviousTagTeams::class, ['stableId' => $this->stable->id]);

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
        $table = livewire(PreviousTagTeams::class, ['stableId' => $this->stable->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
