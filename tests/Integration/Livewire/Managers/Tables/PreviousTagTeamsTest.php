<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousTagTeams;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

it('renders previous tag teams without lazy loading each relationship', function () {
    $manager = Manager::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    TagTeamManager::query()->create([
        'tag_team_id' => $tagTeam->id,
        'manager_id' => $manager->id,
        'hired_at' => now()->subYear(),
        'fired_at' => now()->subMonth(),
    ]);
    $table = new PreviousTagTeams();
    $table->managerId = $manager->id;
    $assignment = $table->builder()->firstOrFail();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $renderedTagTeam = $table->columns()[0]->resolveValue($assignment);

    expect($renderedTagTeam)->toBe($tagTeam->name)
        ->and($assignment->relationLoaded('tagTeam'))->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('forbids users without access to the manager', function (string $actor) {
    $manager = Manager::factory()->create();

    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    livewire(PreviousTagTeams::class, ['managerId' => $manager->id])
        ->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
