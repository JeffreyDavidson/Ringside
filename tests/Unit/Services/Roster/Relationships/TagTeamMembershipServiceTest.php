<?php

declare(strict_types=1);

use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\TagTeams\TagTeamMembershipService;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->service = resolve(TagTeamMembershipService::class);
    $this->tagTeam = TagTeam::factory()->create();
    $this->membershipDate = now()->subDay();
});

it('establishes wrestler and manager memberships with the same date', function () {
    $wrestlers = Wrestler::factory()->count(2)->create();
    $managers = Manager::factory()->count(2)->create();

    $this->service->establishMembership(
        $this->tagTeam,
        new TagTeamMembershipData($wrestlers, $managers),
        $this->membershipDate,
    );

    expect($this->tagTeam->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($wrestlers->modelKeys())
        ->and($this->tagTeam->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing($managers->modelKeys());

    foreach ($wrestlers as $wrestler) {
        $this->assertDatabaseHas('tag_teams_wrestlers', [
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $wrestler->id,
            'joined_at' => $this->membershipDate->toDateTimeString(),
            'left_at' => null,
        ]);
    }

    foreach ($managers as $manager) {
        $this->assertDatabaseHas('tag_teams_managers', [
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $manager->id,
            'hired_at' => $this->membershipDate->toDateTimeString(),
            'fired_at' => null,
        ]);
    }
});

it('synchronizes memberships while preserving relationship history', function () {
    $retainedWrestler = Wrestler::factory()->create();
    $removedWrestler = Wrestler::factory()->create();
    $addedWrestler = Wrestler::factory()->create();
    $removedManager = Manager::factory()->create();
    $addedManager = Manager::factory()->create();
    $this->service->establishMembership(
        $this->tagTeam,
        new TagTeamMembershipData(
            new Collection([$retainedWrestler, $removedWrestler]),
            new Collection([$removedManager]),
        ),
        $this->membershipDate,
    );
    $changeDate = now();

    $this->service->updateMembership(
        $this->tagTeam,
        new TagTeamMembershipData(
            new Collection([$retainedWrestler, $addedWrestler]),
            new Collection([$addedManager]),
        ),
        $changeDate,
    );

    expect($this->tagTeam->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing([$retainedWrestler->id, $addedWrestler->id])
        ->and($this->tagTeam->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing([$addedManager->id]);

    $this->assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $this->tagTeam->id,
        'wrestler_id' => $removedWrestler->id,
        'left_at' => $changeDate->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('tag_teams_managers', [
        'tag_team_id' => $this->tagTeam->id,
        'manager_id' => $removedManager->id,
        'fired_at' => $changeDate->toDateTimeString(),
    ]);
});

it('leaves an omitted membership group unchanged', function () {
    $wrestlers = Wrestler::factory()->count(2)->create();
    $manager = Manager::factory()->create();
    $this->service->establishMembership(
        $this->tagTeam,
        new TagTeamMembershipData($wrestlers, new Collection([$manager])),
        $this->membershipDate,
    );

    $this->service->updateMembership(
        $this->tagTeam,
        new TagTeamMembershipData(wrestlers: $wrestlers),
        now(),
    );

    expect($this->tagTeam->currentManagers()->whereKey($manager->id)->exists())->toBeTrue();
});

it('preserves each wrestler membership when a wrestler rejoins', function () {
    $wrestler = Wrestler::factory()->create();
    $wrestlers = new Collection([$wrestler]);
    $noWrestlers = new Collection();
    $firstJoinedAt = now()->subDays(4)->startOfSecond();
    $firstLeftAt = now()->subDays(3)->startOfSecond();
    $secondJoinedAt = now()->subDays(2)->startOfSecond();
    $secondLeftAt = now()->subDay()->startOfSecond();

    $this->service->establishMembership(
        $this->tagTeam,
        new TagTeamMembershipData(wrestlers: $wrestlers),
        $firstJoinedAt,
    );
    $this->service->updateMembership(
        $this->tagTeam,
        new TagTeamMembershipData(wrestlers: $noWrestlers),
        $firstLeftAt,
    );
    $this->service->establishMembership(
        $this->tagTeam,
        new TagTeamMembershipData(wrestlers: $wrestlers),
        $secondJoinedAt,
    );
    $this->service->updateMembership(
        $this->tagTeam,
        new TagTeamMembershipData(wrestlers: $noWrestlers),
        $secondLeftAt,
    );

    $memberships = TagTeamWrestler::query()
        ->whereBelongsTo($this->tagTeam, 'tagTeam')
        ->whereBelongsTo($wrestler)
        ->orderBy('joined_at')
        ->get();
    $firstMembership = $memberships->firstOrFail();
    $secondMembership = $memberships->skip(1)->firstOrFail();

    expect($memberships)->toHaveCount(2)
        ->and($firstMembership->joined_at->equalTo($firstJoinedAt))->toBeTrue()
        ->and($firstMembership->left_at?->equalTo($firstLeftAt))->toBeTrue()
        ->and($secondMembership->joined_at->equalTo($secondJoinedAt))->toBeTrue()
        ->and($secondMembership->left_at?->equalTo($secondLeftAt))->toBeTrue()
        ->and($this->tagTeam->currentWrestlers()->exists())->toBeFalse();
});
