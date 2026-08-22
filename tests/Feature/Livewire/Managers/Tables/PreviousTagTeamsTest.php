<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousTagTeams;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(User::factory()->administrator()->create());
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
