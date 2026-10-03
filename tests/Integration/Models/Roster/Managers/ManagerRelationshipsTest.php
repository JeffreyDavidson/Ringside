<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

test('manager defines typed wrestler relationships', function () {
    $manager = new Manager;

    expect($manager->wrestlers())->toBeInstanceOf(BelongsToMany::class)
        ->and($manager->wrestlers()->getPivotClass())->toBe(WrestlerManager::class)
        ->and(normalizedSql($manager->currentWrestlers()->toRawSql()))->toContain('"fired_at" is null')
        ->and(normalizedSql($manager->previousWrestlers()->toRawSql()))->toContain('"fired_at" is not null');
});

test('manager defines typed tag team relationships', function () {
    $manager = new Manager;

    expect($manager->tagTeams())->toBeInstanceOf(BelongsToMany::class)
        ->and($manager->tagTeams()->getPivotClass())->toBe(TagTeamManager::class)
        ->and(normalizedSql($manager->currentTagTeams()->toRawSql()))->toContain('"fired_at" is null')
        ->and(normalizedSql($manager->previousTagTeams()->toRawSql()))->toContain('"fired_at" is not null');
});
