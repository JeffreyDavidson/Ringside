<?php

declare(strict_types=1);

use App\Actions\TagTeams\EndMembershipsAction;
use App\Actions\TagTeams\EstablishMembershipAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

test('ending memberships preserves wrestler history', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    resolve(EstablishMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData(new Collection([$wrestler])), now()->subDay());
    resolve(EndMembershipsAction::class)->handle($tagTeam, now());
    expect($tagTeam->previousWrestlers()->whereKey($wrestler)->exists())->toBeTrue();
});
