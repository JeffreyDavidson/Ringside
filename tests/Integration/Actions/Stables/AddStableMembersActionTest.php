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

beforeEach(function () {
    $this->addMembers = resolve(AddStableMembersAction::class);
    $this->removeMembers = resolve(RemoveStableMembersAction::class);
    $this->stable = Stable::factory()->create();
    $this->membershipDate = now()->subDay();
});

it('adds wrestlers and tag teams with the same membership date', function () {
    $wrestlers = Wrestler::factory()->count(2)->create();
    $tagTeams = TagTeam::factory()->count(2)->create();

    $this->addMembers->handle(
        $this->stable,
        new StableMembershipData($wrestlers, $tagTeams),
        $this->membershipDate,
    );

    expect($this->stable->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($wrestlers->modelKeys())
        ->and($this->stable->currentTagTeams()->pluck('tag_teams.id')->all())
        ->toEqualCanonicalizing($tagTeams->modelKeys());

    foreach ($wrestlers as $wrestler) {
        $this->assertDatabaseHas('stables_wrestlers', [
            'stable_id' => $this->stable->id,
            'wrestler_id' => $wrestler->id,
            'joined_at' => $this->membershipDate->toDateTimeString(),
            'left_at' => null,
        ]);
    }

    foreach ($tagTeams as $tagTeam) {
        $this->assertDatabaseHas('stables_tag_teams', [
            'stable_id' => $this->stable->id,
            'tag_team_id' => $tagTeam->id,
            'joined_at' => $this->membershipDate->toDateTimeString(),
            'left_at' => null,
        ]);
    }
});

it('ends wrestler and tag team memberships without deleting their history', function () {
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $members = new StableMembershipData(
        new Collection([$wrestler]),
        new Collection([$tagTeam]),
    );
    $this->addMembers->handle($this->stable, $members, $this->membershipDate);
    $departureDate = now();

    $this->removeMembers->handle($this->stable, $members, $departureDate);

    expect($this->stable->currentWrestlers()->exists())->toBeFalse()
        ->and($this->stable->currentTagTeams()->exists())->toBeFalse()
        ->and($this->stable->previousWrestlers()->whereKey($wrestler->id)->exists())->toBeTrue()
        ->and($this->stable->previousTagTeams()->whereKey($tagTeam->id)->exists())->toBeTrue();
});

it('preserves each membership period when members rejoin a stable', function () {
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

    $this->addMembers->handle($this->stable, $members, $firstJoinedAt);
    $this->removeMembers->handle($this->stable, $members, $firstLeftAt);
    $this->addMembers->handle($this->stable, $members, $secondJoinedAt);
    $this->removeMembers->handle($this->stable, $members, $secondLeftAt);

    $wrestlerMemberships = StableWrestler::query()
        ->whereBelongsTo($this->stable)
        ->whereBelongsTo($wrestler)
        ->orderBy('joined_at')
        ->get();
    $tagTeamMemberships = StableTagTeam::query()
        ->whereBelongsTo($this->stable)
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
        ->and($this->stable->currentWrestlers()->exists())->toBeFalse()
        ->and($this->stable->currentTagTeams()->exists())->toBeFalse();
});
