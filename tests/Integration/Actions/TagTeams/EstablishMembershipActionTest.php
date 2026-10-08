<?php

declare(strict_types=1);

use App\Actions\TagTeams\EndMembershipsAction;
use App\Actions\TagTeams\EstablishMembershipAction;
use App\Actions\TagTeams\SynchronizeMembershipAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\assertDatabaseHas;

it('accepts omitted membership groups', function () {
    resolve(EstablishMembershipAction::class);
    resolve(SynchronizeMembershipAction::class);
    resolve(EndMembershipsAction::class);
    TagTeam::factory()->create();
    now()->subDay();

    $tagTeam = TagTeam::factory()->create();

    resolve(EstablishMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData, now());

    expect($tagTeam->wrestlers()->exists())->toBeFalse();
});

it('establishes wrestler and manager memberships with the same date', function () {
    $establishMembership = resolve(EstablishMembershipAction::class);
    resolve(SynchronizeMembershipAction::class);
    resolve(EndMembershipsAction::class);
    $tagTeam = TagTeam::factory()->create();
    $membershipDate = now()->subDay();

    $wrestlers = Wrestler::factory()->count(2)->create();
    $managers = Manager::factory()->count(2)->create();

    $establishMembership->handle(
        $tagTeam,
        new TagTeamMembershipData($wrestlers, $managers),
        $membershipDate,
    );

    expect($tagTeam->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($wrestlers->modelKeys())
        ->and($tagTeam->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing($managers->modelKeys());

    foreach ($wrestlers as $wrestler) {
        assertDatabaseHas('tag_teams_wrestlers', [
            'tag_team_id' => $tagTeam->id,
            'wrestler_id' => $wrestler->id,
            'joined_at' => $membershipDate->toDateTimeString(),
            'left_at' => null,
        ]);
    }

    foreach ($managers as $manager) {
        assertDatabaseHas('tag_teams_managers', [
            'tag_team_id' => $tagTeam->id,
            'manager_id' => $manager->id,
            'hired_at' => $membershipDate->toDateTimeString(),
            'fired_at' => null,
        ]);
    }
});

it('synchronizes memberships while preserving relationship history', function () {
    $establishMembership = resolve(EstablishMembershipAction::class);
    $synchronizeMembership = resolve(SynchronizeMembershipAction::class);
    resolve(EndMembershipsAction::class);
    $tagTeam = TagTeam::factory()->create();
    $membershipDate = now()->subDay();

    $retainedWrestler = Wrestler::factory()->create();
    $removedWrestler = Wrestler::factory()->create();
    $addedWrestler = Wrestler::factory()->create();
    $removedManager = Manager::factory()->create();
    $addedManager = Manager::factory()->create();
    $establishMembership->handle(
        $tagTeam,
        new TagTeamMembershipData(
            new Collection([$retainedWrestler, $removedWrestler]),
            new Collection([$removedManager]),
        ),
        $membershipDate,
    );
    $changeDate = now();

    $synchronizeMembership->handle(
        $tagTeam,
        new TagTeamMembershipData(
            new Collection([$retainedWrestler, $addedWrestler]),
            new Collection([$addedManager]),
        ),
        $changeDate,
    );

    expect($tagTeam->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing([$retainedWrestler->id, $addedWrestler->id])
        ->and($tagTeam->currentManagers()->pluck('managers.id')->all())
        ->toEqualCanonicalizing([$addedManager->id]);

    assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $removedWrestler->id,
        'left_at' => $changeDate->toDateTimeString(),
    ]);
    assertDatabaseHas('tag_teams_managers', [
        'tag_team_id' => $tagTeam->id,
        'manager_id' => $removedManager->id,
        'fired_at' => $changeDate->toDateTimeString(),
    ]);
});

it('leaves an omitted membership group unchanged', function () {
    $establishMembership = resolve(EstablishMembershipAction::class);
    $synchronizeMembership = resolve(SynchronizeMembershipAction::class);
    resolve(EndMembershipsAction::class);
    $tagTeam = TagTeam::factory()->create();
    $membershipDate = now()->subDay();

    $wrestlers = Wrestler::factory()->count(2)->create();
    $manager = Manager::factory()->create();
    $establishMembership->handle(
        $tagTeam,
        new TagTeamMembershipData($wrestlers, new Collection([$manager])),
        $membershipDate,
    );

    $synchronizeMembership->handle(
        $tagTeam,
        new TagTeamMembershipData(wrestlers: $wrestlers),
        now(),
    );

    expect($tagTeam->currentManagers()->whereKey($manager->id)->exists())->toBeTrue();
});

it('preserves each wrestler membership when a wrestler rejoins', function () {
    $establishMembership = resolve(EstablishMembershipAction::class);
    $synchronizeMembership = resolve(SynchronizeMembershipAction::class);
    resolve(EndMembershipsAction::class);
    $tagTeam = TagTeam::factory()->create();
    now()->subDay();

    $wrestler = Wrestler::factory()->create();
    $wrestlers = new Collection([$wrestler]);
    $noWrestlers = new Collection;
    $firstJoinedAt = now()->subDays(4)->startOfSecond();
    $firstLeftAt = now()->subDays(3)->startOfSecond();
    $secondJoinedAt = now()->subDays(2)->startOfSecond();
    $secondLeftAt = now()->subDay()->startOfSecond();

    $establishMembership->handle(
        $tagTeam,
        new TagTeamMembershipData(wrestlers: $wrestlers),
        $firstJoinedAt,
    );
    $synchronizeMembership->handle(
        $tagTeam,
        new TagTeamMembershipData(wrestlers: $noWrestlers),
        $firstLeftAt,
    );
    $establishMembership->handle(
        $tagTeam,
        new TagTeamMembershipData(wrestlers: $wrestlers),
        $secondJoinedAt,
    );
    $synchronizeMembership->handle(
        $tagTeam,
        new TagTeamMembershipData(wrestlers: $noWrestlers),
        $secondLeftAt,
    );

    $memberships = TagTeamWrestler::query()
        ->whereBelongsTo($tagTeam, 'tagTeam')
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
        ->and($tagTeam->currentWrestlers()->exists())->toBeFalse();
});
