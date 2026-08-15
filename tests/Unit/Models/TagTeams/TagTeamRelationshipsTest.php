<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamManager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

it('defines manager assignment relationships', function () {
    $tagTeam = new TagTeam();
    $managers = $tagTeam->managers();

    expect($managers)->toBeInstanceOf(BelongsToMany::class)
        ->and($managers->getTable())->toBe((new TagTeamManager())->getTable())
        ->and($managers->getPivotClass())->toBe(TagTeamManager::class)
        ->and($managers->getPivotColumns())->toContain('hired_at', 'fired_at', 'created_at', 'updated_at')
        ->and($tagTeam->currentManagers()->toRawSql())->toContain('"fired_at" is null')
        ->and($tagTeam->previousManagers()->toRawSql())->toContain('"fired_at" is not null');
});

it('defines stable membership relationships', function () {
    $tagTeam = new TagTeam();
    $stables = $tagTeam->stables();
    $currentStable = $tagTeam->currentStable();

    expect($stables)->toBeInstanceOf(BelongsToMany::class)
        ->and($currentStable)->toBeInstanceOf(HasOneThrough::class)
        ->and($stables->getRelated())->toBeInstanceOf(Stable::class)
        ->and($stables->getTable())->toBe((new StableTagTeam())->getTable())
        ->and($stables->getForeignPivotKeyName())->toBe('tag_team_id')
        ->and($stables->getPivotClass())->toBe(StableTagTeam::class)
        ->and($stables->getPivotColumns())->toContain('joined_at', 'left_at', 'created_at', 'updated_at')
        ->and($currentStable->toRawSql())->toContain('"stables_tag_teams"."left_at" is null')
        ->and($tagTeam->previousStables()->toRawSql())->toContain('"left_at" is not null');
});
