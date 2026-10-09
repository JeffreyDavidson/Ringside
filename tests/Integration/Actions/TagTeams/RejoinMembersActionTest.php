<?php

declare(strict_types=1);

use App\Actions\TagTeams\RejoinMembersAction;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;

test('it starts a new membership for a wrestler who left the tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $leaver = Wrestler::factory()->create();
    $tagTeam->wrestlers()->attach($leaver, ['joined_at' => now()->subMonth(), 'left_at' => now()->subWeek()]);
    $date = now();

    resolve(RejoinMembersAction::class)->handle($tagTeam, Wrestler::query()->whereKey($leaver->id)->get(), $date);

    expect($tagTeam->currentWrestlers()->pluck('wrestlers.id')->all())->toBe([$leaver->id])
        ->and(TagTeamWrestler::query()->forTagTeamId($tagTeam->id)->forWrestlerId($leaver->id)->count())->toBe(2);
});

test('it leaves wrestlers who are already current members alone', function () {
    $tagTeam = TagTeam::factory()->unemployed()->create();
    $member = $tagTeam->currentWrestlers()->firstOrFail();

    resolve(RejoinMembersAction::class)->handle($tagTeam, Wrestler::query()->whereKey($member->id)->get(), now());

    expect(TagTeamWrestler::query()->forTagTeamId($tagTeam->id)->forWrestlerId($member->id)->count())->toBe(1);
});
