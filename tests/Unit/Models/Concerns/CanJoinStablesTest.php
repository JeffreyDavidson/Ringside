<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

dataset('stable members', [
    'wrestler' => [fn (): Wrestler => Wrestler::factory()->make()],
    'tag team' => [fn (): TagTeam => TagTeam::factory()->make()],
]);

dataset('stable relationship configurations', [
    'wrestler' => [fn (): Wrestler => Wrestler::factory()->make(), 'stables_wrestlers', 'wrestler_id', StableWrestler::class],
    'tag team' => [fn (): TagTeam => TagTeam::factory()->make(), 'stables_tag_teams', 'tag_team_id', StableTagTeam::class],
]);

it('defines all stable relationships', function (Wrestler|TagTeam $member) {
    $stables = $member->stables();
    $currentStable = $member->currentStable();
    $previousStables = $member->previousStables();

    expect($stables)->toBeInstanceOf(BelongsToMany::class)
        ->and($currentStable)->toBeInstanceOf(BelongsToOne::class)
        ->and($previousStables)->toBeInstanceOf(BelongsToMany::class)
        ->and($stables->getRelated())->toBeInstanceOf(Stable::class)
        ->and($currentStable->getRelated())->toBeInstanceOf(Stable::class)
        ->and($previousStables->getRelated())->toBeInstanceOf(Stable::class);
})->with('stable members');

it('uses the configured stable membership table and pivot model', function (
    Wrestler|TagTeam $member,
    string $table,
    string $foreignKey,
    string $pivotClass,
) {
    $relationship = $member->stables();
    $pivot = $relationship->newPivot();

    expect($relationship->getTable())->toBe($table)
        ->and($relationship->getForeignPivotKeyName())->toBe($foreignKey)
        ->and($pivot)->toBeInstanceOf(Pivot::class)
        ->and($pivot::class)->toBe($pivotClass);
})->with('stable relationship configurations');

it('includes stable membership dates and timestamps', function (Wrestler|TagTeam $member) {
    expect($member->stables()->getPivotColumns())
        ->toContain('joined_at', 'left_at', 'created_at', 'updated_at');
})->with('stable members');

it('filters current and previous stable memberships by the departure date', function (
    Wrestler|TagTeam $member,
    string $table,
) {
    $qualifiedDepartureColumn = "\"{$table}\".\"left_at\"";

    $currentStableQuery = $member->currentStable()->getQuery()->getQuery();
    $previousStablesQuery = $member->previousStables()->getQuery()->getQuery();

    expect($currentStableQuery->toRawSql())->toContain("{$qualifiedDepartureColumn} is null")
        ->and($previousStablesQuery->toRawSql())->toContain("{$qualifiedDepartureColumn} is not null");
})->with([
    'wrestler' => [fn (): Wrestler => Wrestler::factory()->make(), 'stables_wrestlers'],
    'tag team' => [fn (): TagTeam => TagTeam::factory()->make(), 'stables_tag_teams'],
]);
