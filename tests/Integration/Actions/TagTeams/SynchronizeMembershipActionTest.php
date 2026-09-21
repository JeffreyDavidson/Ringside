<?php

declare(strict_types=1);

use App\Actions\TagTeams\EstablishMembershipAction;
use App\Actions\TagTeams\SynchronizeMembershipAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

test('synchronizing membership dates ended wrestler pivots', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $date = now();
    resolve(EstablishMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData(new Collection([$wrestler])), now()->subDay());
    resolve(SynchronizeMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData(wrestlers: new Collection), $date);
    expect($tagTeam->previousWrestlers()->whereKey($wrestler)->exists())->toBeTrue();
});

test('synchronizing omitted wrestler memberships leaves them unchanged', function () {
    $tagTeam = TagTeam::factory()->create();

    resolve(SynchronizeMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData, now());

    expect($tagTeam->currentWrestlers()->exists())->toBeFalse();
});
