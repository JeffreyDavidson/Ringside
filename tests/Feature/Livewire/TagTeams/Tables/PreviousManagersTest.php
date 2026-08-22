<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousManagers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(User::factory()->administrator()->create());
});

it('renders previous managers without lazy loading each relationship', function () {
    $tagTeam = TagTeam::factory()->create();
    $manager = Manager::factory()->create()->refresh();
    TagTeamManager::query()->create([
        'tag_team_id' => $tagTeam->id,
        'manager_id' => $manager->id,
        'hired_at' => now()->subYear(),
        'fired_at' => now()->subMonth(),
    ]);
    $table = new PreviousManagers();
    $table->tagTeamId = $tagTeam->id;
    $assignment = $table->builder()->firstOrFail();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $renderedManager = $table->columns()[0]->resolveValue($assignment);

    expect($renderedManager)->toBe($manager->full_name)
        ->and($assignment->relationLoaded('manager'))->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();
});
