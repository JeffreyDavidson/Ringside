<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousWrestlers;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('requires a tag team', function (): void {
    expect(fn () => (new PreviousWrestlers())->builder())
        ->toThrow(LogicException::class, 'A tag team was not provided.');
});

it('returns only ended memberships for the requested tag team in newest-first order', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $otherTagTeam = TagTeam::factory()->create();
    $recentWrestler = Wrestler::factory()->create();
    $olderWrestler = Wrestler::factory()->create();
    $currentWrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();

    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $olderWrestler->id,
        'joined_at' => Date::now()->subMonths(3),
        'left_at' => Date::now()->subMonths(2),
    ]);
    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $recentWrestler->id,
        'joined_at' => Date::now()->subMonth(),
        'left_at' => Date::now()->subWeek(),
    ]);
    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $currentWrestler->id,
        'joined_at' => Date::now()->subDays(3),
        'left_at' => null,
    ]);
    TagTeamWrestler::query()->create([
        'tag_team_id' => $otherTagTeam->id,
        'wrestler_id' => $otherWrestler->id,
        'joined_at' => Date::now()->subDays(2),
        'left_at' => Date::now()->subDay(),
    ]);

    $table = new PreviousWrestlers();
    $table->tagTeamId = $tagTeam->id;

    // Act
    $memberships = $table->builder()->get();

    // Assert
    expect($memberships->pluck('wrestler_id')->all())->toBe([
        $recentWrestler->id,
        $olderWrestler->id,
    ])->and($memberships->every->relationLoaded('wrestler'))->toBeTrue();
});

it('renders previous wrestler links and membership dates', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $previousWrestler = Wrestler::factory()->create(['name' => 'Previous Wrestler']);
    $currentWrestler = Wrestler::factory()->create(['name' => 'Current Wrestler']);
    $joinedAt = Date::now()->subMonth();
    $leftAt = Date::now()->subWeek();

    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $previousWrestler->id,
        'joined_at' => $joinedAt,
        'left_at' => $leftAt,
    ]);
    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $currentWrestler->id,
        'joined_at' => Date::now()->subDay(),
        'left_at' => null,
    ]);

    // Act
    $component = livewire(PreviousWrestlers::class, ['tagTeamId' => $tagTeam->id]);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Previous Wrestler')
        ->assertSee(route('wrestlers.show', $previousWrestler))
        ->assertSee($joinedAt->format('Y-m-d'))
        ->assertSee($leftAt->format('Y-m-d'))
        ->assertDontSee('Current Wrestler');
});

it('keeps separate historical memberships for a returning wrestler', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();

    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => Date::now()->subMonths(4),
        'left_at' => Date::now()->subMonths(3),
    ]);
    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => Date::now()->subMonths(2),
        'left_at' => Date::now()->subMonth(),
    ]);

    $table = new PreviousWrestlers();
    $table->tagTeamId = $tagTeam->id;

    // Act
    $memberships = $table->builder()->get();

    // Assert
    expect($memberships)->toHaveCount(2)
        ->and($memberships->pluck('wrestler_id')->all())->toBe([
            $wrestler->id,
            $wrestler->id,
        ]);
});

it('renders an unknown wrestler when the related wrestler was deleted', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => Date::now()->subMonth(),
        'left_at' => Date::now()->subWeek(),
    ]);
    $wrestler->delete();

    // Act
    $component = livewire(PreviousWrestlers::class, ['tagTeamId' => $tagTeam->id]);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Unknown');
});

it('renders an empty state without previous wrestlers', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create();

    // Act
    $component = livewire(PreviousWrestlers::class, ['tagTeamId' => $tagTeam->id]);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('No records found.');
});

it('forbids users without access to the tag team', function (string $actor): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create();

    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    // Act
    $component = livewire(PreviousWrestlers::class, ['tagTeamId' => $tagTeam->id]);

    // Assert
    $component->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
