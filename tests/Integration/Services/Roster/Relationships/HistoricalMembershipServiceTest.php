<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Relationships\HistoricalMembershipService;
use Illuminate\Database\Eloquent\Collection;

it('adds memberships with an open historical period', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestlers = Wrestler::factory()->count(2)->create();
    $joinedAt = now()->subDay()->startOfSecond();

    resolve(HistoricalMembershipService::class)->add(
        $tagTeam->wrestlers(),
        new Collection($wrestlers->all()),
        $joinedAt,
    );

    expect($tagTeam->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($wrestlers->modelKeys());

    foreach ($wrestlers as $wrestler) {
        $this->assertDatabaseHas('tag_teams_wrestlers', [
            'tag_team_id' => $tagTeam->id,
            'wrestler_id' => $wrestler->id,
            'joined_at' => $joinedAt->toDateTimeString(),
            'left_at' => null,
        ]);
    }
});

it('removes only current memberships while preserving history', function () {
    $tagTeam = TagTeam::factory()->create();
    $historicalWrestler = Wrestler::factory()->create();
    $currentWrestler = Wrestler::factory()->create();
    $historicalEnd = now()->subDay()->startOfSecond();
    $leftAt = now()->startOfSecond();

    $tagTeam->wrestlers()->attach($historicalWrestler, [
        'joined_at' => now()->subDays(3),
        'left_at' => $historicalEnd,
    ]);
    $tagTeam->wrestlers()->attach($currentWrestler, [
        'joined_at' => now()->subDay(),
        'left_at' => null,
    ]);

    resolve(HistoricalMembershipService::class)->remove(
        $tagTeam->wrestlers(),
        new Collection([$currentWrestler]),
        $leftAt,
    );

    $historicalMembership = TagTeamWrestler::query()
        ->whereBelongsTo($tagTeam)
        ->whereBelongsTo($historicalWrestler, 'wrestler')
        ->firstOrFail();
    $endedMembership = TagTeamWrestler::query()
        ->whereBelongsTo($tagTeam)
        ->whereBelongsTo($currentWrestler, 'wrestler')
        ->firstOrFail();

    expect($historicalMembership->left_at?->equalTo($historicalEnd))->toBeTrue()
        ->and($endedMembership->left_at?->equalTo($leftAt))->toBeTrue()
        ->and($tagTeam->currentWrestlers()->exists())->toBeFalse();
});

it('synchronizes membership changes without rewriting retained history', function () {
    $tagTeam = TagTeam::factory()->create();
    $removedWrestler = Wrestler::factory()->create();
    $retainedWrestler = Wrestler::factory()->create();
    $addedWrestler = Wrestler::factory()->create();
    $joinedAt = now()->subDay()->startOfSecond();
    $changedAt = now()->startOfSecond();

    $tagTeam->wrestlers()->attach($removedWrestler, [
        'joined_at' => $joinedAt,
        'left_at' => null,
    ]);
    $tagTeam->wrestlers()->attach($retainedWrestler, [
        'joined_at' => $joinedAt,
        'left_at' => null,
    ]);

    resolve(HistoricalMembershipService::class)->synchronize(
        $tagTeam->wrestlers(),
        new Collection([$removedWrestler, $retainedWrestler]),
        new Collection([$retainedWrestler, $addedWrestler]),
        $changedAt,
    );

    $removedMembership = TagTeamWrestler::query()
        ->whereBelongsTo($tagTeam)
        ->whereBelongsTo($removedWrestler, 'wrestler')
        ->firstOrFail();
    $retainedMembership = TagTeamWrestler::query()
        ->whereBelongsTo($tagTeam)
        ->whereBelongsTo($retainedWrestler, 'wrestler')
        ->firstOrFail();

    expect($tagTeam->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing([$retainedWrestler->id, $addedWrestler->id])
        ->and($removedMembership->left_at?->equalTo($changedAt))->toBeTrue()
        ->and($retainedMembership->joined_at?->equalTo($joinedAt))->toBeTrue()
        ->and($retainedMembership->left_at)->toBeNull();
});
