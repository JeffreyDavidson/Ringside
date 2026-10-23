<?php

declare(strict_types=1);

use App\Actions\TagTeams\EstablishMembershipAction;
use App\Actions\TagTeams\SynchronizeMembershipAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

use function Pest\Laravel\travelTo;

test('synchronizing membership dates ended wrestler pivots', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $date = now();
    resolve(EstablishMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData(new Collection([$wrestler])), now()->subDay());
    resolve(SynchronizeMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData(wrestlers: new Collection), $date);
    expect($tagTeam->previousWrestlers()->whereKey($wrestler)->exists())->toBeTrue();
});

test('synchronizing omitted wrestler memberships ends the current ones, like an empty list', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => today()->subDay()]);

    // Act
    resolve(SynchronizeMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData, today());

    // Assert
    expect($tagTeam->currentWrestlers()->exists())->toBeFalse()
        ->and($tagTeam->previousWrestlers()->whereKey($wrestler)->exists())->toBeTrue();
});

test('synchronizing ends not-yet-started wrestler memberships on their own join date', function () {
    $tagTeam = TagTeam::factory()->create();
    $startedWrestler = Wrestler::factory()->create();
    $futureWrestler = Wrestler::factory()->create();
    $start = today()->addWeek();
    $tagTeam->wrestlers()->attach($startedWrestler, ['joined_at' => today()->subDay()]);
    $tagTeam->wrestlers()->attach($futureWrestler, ['joined_at' => $start]);

    resolve(SynchronizeMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData(wrestlers: new Collection), today());

    expect($tagTeam->wrestlers()->whereKey($startedWrestler->getKey())->wherePivot('left_at', today())->exists())->toBeTrue()
        ->and($tagTeam->wrestlers()->whereKey($futureWrestler->getKey())->wherePivot('left_at', $start)->exists())->toBeTrue();
});

describe('a tag team holding a title', function (): void {
    test('it vacates the title on the date a member is removed, even when a new member replaces them', function (bool $replaced) {
        // Arrange
        travelTo(Carbon::parse('2026-03-10 09:00:00'));
        $date = now();
        $tagTeam = TagTeam::factory()
            ->employed()
            ->create();
        $kept = $tagTeam->currentWrestlers()->inLockOrder()->firstOrFail();
        $replacement = Wrestler::factory()->create();
        $reign = TitleChampionship::factory()
            ->for(Title::factory()->tagTeam(), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();
        $members = new Collection($replaced ? [$kept, $replacement] : [$kept]);

        // Act
        resolve(SynchronizeMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData(wrestlers: $members), $date);

        // Assert
        expect($reign->refresh()->lost_at?->toDateTimeString())->toBe('2026-03-10 09:00:00');
    })->with([
        'removed' => false,
        'replaced' => true,
    ]);

    test('it keeps the title when a member is added or the membership is unchanged', function () {
        // Arrange
        $tagTeam = TagTeam::factory()
            ->employed()
            ->create();
        $current = $tagTeam->currentWrestlers()->inLockOrder()->get();
        $reign = TitleChampionship::factory()
            ->for(Title::factory()->tagTeam(), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();

        // Act
        resolve(SynchronizeMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData(wrestlers: new Collection($current->all())), now());
        resolve(SynchronizeMembershipAction::class)->handle($tagTeam, new TagTeamMembershipData(wrestlers: new Collection([...$current->all(), Wrestler::factory()->create()])), now());

        // Assert
        expect($reign->refresh()->lost_at)->toBeNull();
    });
});
