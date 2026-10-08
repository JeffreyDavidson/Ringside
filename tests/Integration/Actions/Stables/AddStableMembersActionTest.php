<?php

declare(strict_types=1);

use App\Actions\Stables\AddStableMembersAction;
use App\Actions\Stables\RemoveStableMembersAction;
use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\assertDatabaseHas;

it('adds wrestlers and tag teams with the same membership date', function () {
    $addMembers = resolve(AddStableMembersAction::class);
    resolve(RemoveStableMembersAction::class);
    $stable = Stable::factory()->create();
    $membershipDate = now()->subDay();

    $wrestlers = Wrestler::factory()->count(2)->create();
    $tagTeams = TagTeam::factory()->count(2)->create();

    $addMembers->handle(
        $stable,
        new StableMembershipData($wrestlers, $tagTeams),
        $membershipDate,
    );

    expect($stable->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($wrestlers->modelKeys())
        ->and($stable->currentTagTeams()->pluck('tag_teams.id')->all())
        ->toEqualCanonicalizing($tagTeams->modelKeys());

    foreach ($wrestlers as $wrestler) {
        assertDatabaseHas('stables_wrestlers', [
            'stable_id' => $stable->id,
            'wrestler_id' => $wrestler->id,
            'joined_at' => $membershipDate->toDateTimeString(),
            'left_at' => null,
        ]);
    }

    foreach ($tagTeams as $tagTeam) {
        assertDatabaseHas('stables_tag_teams', [
            'stable_id' => $stable->id,
            'tag_team_id' => $tagTeam->id,
            'joined_at' => $membershipDate->toDateTimeString(),
            'left_at' => null,
        ]);
    }
});

it('ends wrestler and tag team memberships without deleting their history', function () {
    $addMembers = resolve(AddStableMembersAction::class);
    $removeMembers = resolve(RemoveStableMembersAction::class);
    $stable = Stable::factory()->create();
    $membershipDate = now()->subDay();

    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $members = new StableMembershipData(
        new Collection([$wrestler]),
        new Collection([$tagTeam]),
    );
    $addMembers->handle($stable, $members, $membershipDate);
    $departureDate = now();

    $removeMembers->handle($stable, $members, $departureDate);

    expect($stable->currentWrestlers()->exists())->toBeFalse()
        ->and($stable->currentTagTeams()->exists())->toBeFalse()
        ->and($stable->previousWrestlers()->whereKey($wrestler->id)->exists())->toBeTrue()
        ->and($stable->previousTagTeams()->whereKey($tagTeam->id)->exists())->toBeTrue();
});

it('preserves each membership period when members rejoin a stable', function () {
    $addMembers = resolve(AddStableMembersAction::class);
    $removeMembers = resolve(RemoveStableMembersAction::class);
    $stable = Stable::factory()->create();
    now()->subDay();

    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $members = new StableMembershipData(
        wrestlers: new Collection([$wrestler]),
        tagTeams: new Collection([$tagTeam]),
    );
    $firstJoinedAt = now()->subDays(4)->startOfSecond();
    $firstLeftAt = now()->subDays(3)->startOfSecond();
    $secondJoinedAt = now()->subDays(2)->startOfSecond();
    $secondLeftAt = now()->subDay()->startOfSecond();

    $addMembers->handle($stable, $members, $firstJoinedAt);
    $removeMembers->handle($stable, $members, $firstLeftAt);
    $addMembers->handle($stable, $members, $secondJoinedAt);
    $removeMembers->handle($stable, $members, $secondLeftAt);

    $wrestlerMemberships = StableWrestler::query()
        ->whereBelongsTo($stable)
        ->whereBelongsTo($wrestler)
        ->orderBy('joined_at')
        ->get();
    $tagTeamMemberships = StableTagTeam::query()
        ->whereBelongsTo($stable)
        ->whereBelongsTo($tagTeam, 'tagTeam')
        ->orderBy('joined_at')
        ->get();
    $firstWrestlerMembership = $wrestlerMemberships->firstOrFail();
    $secondWrestlerMembership = $wrestlerMemberships->skip(1)->firstOrFail();
    $firstTagTeamMembership = $tagTeamMemberships->firstOrFail();
    $secondTagTeamMembership = $tagTeamMemberships->skip(1)->firstOrFail();

    expect($wrestlerMemberships)->toHaveCount(2)
        ->and($firstWrestlerMembership->joined_at->equalTo($firstJoinedAt))->toBeTrue()
        ->and($firstWrestlerMembership->left_at?->equalTo($firstLeftAt))->toBeTrue()
        ->and($secondWrestlerMembership->joined_at->equalTo($secondJoinedAt))->toBeTrue()
        ->and($secondWrestlerMembership->left_at?->equalTo($secondLeftAt))->toBeTrue()
        ->and($tagTeamMemberships)->toHaveCount(2)
        ->and($firstTagTeamMembership->joined_at->equalTo($firstJoinedAt))->toBeTrue()
        ->and($firstTagTeamMembership->left_at?->equalTo($firstLeftAt))->toBeTrue()
        ->and($secondTagTeamMembership->joined_at->equalTo($secondJoinedAt))->toBeTrue()
        ->and($secondTagTeamMembership->left_at?->equalTo($secondLeftAt))->toBeTrue()
        ->and($stable->currentWrestlers()->exists())->toBeFalse()
        ->and($stable->currentTagTeams()->exists())->toBeFalse();
});
