<?php

declare(strict_types=1);

use App\Actions\Managers\EndCurrentRelationshipsAction as EndManagerRelationships;
use App\Actions\Managers\EndManagerAssignmentsAction;
use App\Actions\TagTeams\EndMembershipsAction;
use App\Actions\Wrestlers\EndCurrentRelationshipsAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

test('ending a wrestler relationships clamps rows that have not started yet', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $startedTagTeam = TagTeam::factory()->create();
    $futureStable = Stable::factory()->create();
    $manager = Manager::factory()->create();
    $futureStart = now()->addDays(5)->startOfSecond();
    $wrestler->tagTeams()->attach($startedTagTeam, ['joined_at' => now()->subDay()]);
    $wrestler->stables()->attach($futureStable, ['joined_at' => $futureStart]);
    $wrestler->managers()->attach($manager, ['hired_at' => $futureStart]);

    // Act
    resolve(EndCurrentRelationshipsAction::class)->handle($wrestler, now());

    // Assert
    $futureRow = DB::table('stables_wrestlers')->where('stable_id', $futureStable->id)->sole();
    $startedRow = DB::table('tag_teams_wrestlers')->where('tag_team_id', $startedTagTeam->id)->sole();
    $managerRow = DB::table('wrestlers_managers')->where('manager_id', $manager->id)->sole();

    expect($futureRow->left_at)->toBe($futureRow->joined_at)
        ->and($startedRow->left_at)->toBe(now()->toDateTimeString())
        ->and($managerRow->fired_at)->toBe($managerRow->hired_at);
});

test('ending tag team memberships and manager assignments clamps rows that have not started yet', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $member = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    $futureStart = now()->addDays(5)->startOfSecond();
    $tagTeam->wrestlers()->attach($member, ['joined_at' => $futureStart]);
    $tagTeam->managers()->attach($manager, ['hired_at' => $futureStart]);

    // Act
    resolve(EndMembershipsAction::class)->handle($tagTeam, now());

    // Assert
    $membership = DB::table('tag_teams_wrestlers')->where('wrestler_id', $member->id)->sole();
    $assignment = DB::table('tag_teams_managers')->where('manager_id', $manager->id)->sole();

    expect($membership->left_at)->toBe($membership->joined_at)
        ->and($assignment->fired_at)->toBe($assignment->hired_at);
});

test('ending manager assignments for a manager clamps rows that have not started yet', function () {
    // Arrange
    $manager = Manager::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $futureStart = now()->addDays(5)->startOfSecond();
    $manager->wrestlers()->attach($wrestler, ['hired_at' => $futureStart]);
    $manager->tagTeams()->attach($tagTeam, ['hired_at' => now()->subDay()]);

    // Act
    resolve(EndManagerRelationships::class)->handle($manager, now());

    // Assert
    $future = DB::table('wrestlers_managers')->where('manager_id', $manager->id)->sole();
    $started = DB::table('tag_teams_managers')->where('manager_id', $manager->id)->sole();

    expect($future->fired_at)->toBe($future->hired_at)
        ->and($started->fired_at)->toBe(now()->toDateTimeString());
});

test('ending manager assignments of a wrestler keeps already ended rows untouched', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    $firedAt = now()->subDay()->startOfSecond();
    $wrestler->managers()->attach($manager, ['hired_at' => now()->subWeek(), 'fired_at' => $firedAt]);

    // Act
    resolve(EndManagerAssignmentsAction::class)->handle($wrestler, now());

    // Assert
    expect(DB::table('wrestlers_managers')->where('manager_id', $manager->id)->value('fired_at'))
        ->toBe($firedAt->toDateTimeString());
});
