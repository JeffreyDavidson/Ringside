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
    actingAs(administrator());
});

it('requires a stable', function (): void {
    expect(fn () => (new PreviousTagTeams())->builder())
        ->toThrow(LogicException::class, 'A stable was not provided.');
});

it('returns only ended tag team memberships for the requested stable in newest-first order', function (): void {
    // Arrange
    $stable = Stable::factory()->create();
    $otherStable = Stable::factory()->create();
    $recentTagTeam = TagTeam::factory()->create();
    $olderTagTeam = TagTeam::factory()->create();
    $currentTagTeam = TagTeam::factory()->create();
    $otherTagTeam = TagTeam::factory()->create();

    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $olderTagTeam->id,
        'joined_at' => Date::now()->subMonths(4),
        'left_at' => Date::now()->subMonths(3),
    ]);
    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $recentTagTeam->id,
        'joined_at' => Date::now()->subMonths(2),
        'left_at' => Date::now()->subMonth(),
    ]);
    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
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
    $table->stableId = $stable->id;

    // Act
    $memberships = $table->builder()->get();

    // Assert
    expect($memberships->pluck('tag_team_id')->all())->toBe([
        $recentTagTeam->id,
        $olderTagTeam->id,
    ])->and($memberships->every->relationLoaded('tagTeam'))->toBeTrue();
});

it('renders previous tag team links and membership dates', function (): void {
    // Arrange
    $stable = Stable::factory()->create();
    $formerTagTeam = TagTeam::factory()->create(['name' => 'Former Tag Team']);
    $currentTagTeam = TagTeam::factory()->create(['name' => 'Current Tag Team']);
    $joinedAt = Date::now()->subMonths(3);
    $leftAt = Date::now()->subMonth();

    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $formerTagTeam->id,
        'joined_at' => $joinedAt,
        'left_at' => $leftAt,
    ]);
    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $currentTagTeam->id,
        'joined_at' => Date::now()->subWeek(),
        'left_at' => null,
    ]);

    // Act
    $component = livewire(PreviousTagTeams::class, ['stableId' => $stable->id]);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Former Tag Team')
        ->assertSee(route('tag-teams.show', $formerTagTeam))
        ->assertSee($joinedAt->format('Y-m-d'))
        ->assertSee($leftAt->format('Y-m-d'))
        ->assertDontSee('Current Tag Team');
});

it('renders an unknown tag team when the related tag team was deleted', function (): void {
    // Arrange
    $stable = Stable::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $tagTeam->id,
        'joined_at' => Date::now()->subMonth(),
        'left_at' => Date::now()->subWeek(),
    ]);
    $tagTeam->delete();

    // Act
    $component = livewire(PreviousTagTeams::class, ['stableId' => $stable->id]);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Unknown');
});

it('forbids users without access to the stable', function (string $actor): void {
    // Arrange
    $stable = Stable::factory()->create();

    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    // Act
    $component = livewire(PreviousTagTeams::class, ['stableId' => $stable->id]);

    // Assert
    $component->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
