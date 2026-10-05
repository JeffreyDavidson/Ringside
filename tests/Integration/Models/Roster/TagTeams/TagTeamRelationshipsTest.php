<?php

declare(strict_types=1);

use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

it('defines manager assignment relationships', function () {
    $tagTeam = new TagTeam;
    $managers = $tagTeam->managers();

    expect($managers)->toBeInstanceOf(BelongsToMany::class)
        ->and($managers->getTable())->toBe((new TagTeamManager)->getTable())
        ->and($managers->getPivotClass())->toBe(TagTeamManager::class)
        ->and($managers->getPivotColumns())->toContain('hired_at', 'fired_at', 'created_at', 'updated_at')
        ->and(normalizedSql($tagTeam->currentManagers()->toRawSql()))->toContain('"fired_at" is null')
        ->and(normalizedSql($tagTeam->previousManagers()->toRawSql()))->toContain('"fired_at" is not null');
});

it('defines stable membership relationships', function () {
    $tagTeam = new TagTeam;
    $stables = $tagTeam->stables();
    $currentStable = $tagTeam->currentStable();

    expect($stables)->toBeInstanceOf(BelongsToMany::class)
        ->and($currentStable)->toBeInstanceOf(HasOneThrough::class)
        ->and($stables->getRelated())->toBeInstanceOf(Stable::class)
        ->and($stables->getTable())->toBe((new StableTagTeam)->getTable())
        ->and($stables->getForeignPivotKeyName())->toBe('tag_team_id')
        ->and($stables->getPivotClass())->toBe(StableTagTeam::class)
        ->and($stables->getPivotColumns())->toContain('joined_at', 'left_at', 'created_at', 'updated_at')
        ->and(normalizedSql($currentStable->toRawSql()))->toContain('"stables_tag_teams"."left_at" is null')
        ->and(normalizedSql($tagTeam->previousStables()->toRawSql()))->toContain('"left_at" is not null');
});

it('reports injured and suspended current members', function (bool $injured, bool $suspended): void {
    // Arrange
    $member = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->employed()->withCurrentWrestlers(collect([$member]))->create();

    if ($injured) {
        Injury::factory()->for($member, 'injurable')->create();
    }

    if ($suspended) {
        Suspension::factory()->for($member, 'suspendable')->create();
    }

    // Act
    $tagTeam = TagTeam::query()->findOrFail($tagTeam->id);

    // Assert
    expect($tagTeam->hasInjuredMember())->toBe($injured)
        ->and($tagTeam->hasSuspendedMember())->toBe($suspended);
})->with([
    'injured member' => [true, false],
    'suspended member' => [false, true],
    'injured and suspended member' => [true, true],
    'healthy team' => [false, false],
]);

it('ignores injuries and suspensions of former members', function (): void {
    // Arrange
    $former = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->employed()->create();
    $tagTeam->wrestlers()->attach($former, ['joined_at' => now()->subYear(), 'left_at' => now()->subMonth()]);
    Injury::factory()->for($former, 'injurable')->create();
    Suspension::factory()->for($former, 'suspendable')->create();

    // Act
    $tagTeam = TagTeam::query()->findOrFail($tagTeam->id);

    // Assert
    expect($tagTeam->hasInjuredMember())->toBeFalse()
        ->and($tagTeam->hasSuspendedMember())->toBeFalse();
});
