<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeamWrestler;
use Illuminate\Database\Eloquent\Relations\Pivot;

test('defines its pivot persistence metadata', function () {
    $membership = new TagTeamWrestler();

    expect($membership)->toBeInstanceOf(Pivot::class)
        ->and($membership->getTable())->toBe('tag_teams_wrestlers')
        ->and($membership->getFillable())->toBe([
            'tag_team_id',
            'wrestler_id',
            'joined_at',
            'left_at',
        ])
        ->and($membership->getCasts())
        ->toMatchArray([
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ]);
});
