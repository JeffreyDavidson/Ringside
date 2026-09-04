<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousStables;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

it('requires a tag team', function () {
    expect(fn () => (new PreviousStables())->builder())
        ->toThrow(LogicException::class, 'A tag team was not provided.');
});

it('shows only previous stables for the selected tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $otherTagTeam = TagTeam::factory()->create();
    $previousStable = Stable::factory()->create();
    $currentStable = Stable::factory()->create();
    $otherStable = Stable::factory()->create();
    $previousStable->tagTeams()->attach($tagTeam, [
        'joined_at' => now()->subMonths(2),
        'left_at' => now()->subMonth(),
    ]);
    $currentStable->tagTeams()->attach($tagTeam, [
        'joined_at' => now(),
    ]);
    $otherStable->tagTeams()->attach($otherTagTeam, [
        'joined_at' => now()->subMonths(2),
        'left_at' => now()->subMonth(),
    ]);
    $stables = tap(app(PreviousStables::class), fn (PreviousStables $table) => $table->tagTeamId = $tagTeam->id)
        ->builder()
        ->get();

    expect($stables)->toHaveCount(1)
        ->and($stables->firstOrFail()->is($previousStable))->toBeTrue();
});

it('renders for an administrator', function () {
    $tagTeam = TagTeam::factory()->create();

    livewire(PreviousStables::class, ['tagTeamId' => $tagTeam->id])
        ->assertSuccessful();
});

it('forbids users without access to the tag team', function (string $actor) {
    $tagTeam = TagTeam::factory()->create();

    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    livewire(PreviousStables::class, ['tagTeamId' => $tagTeam->id])
        ->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
