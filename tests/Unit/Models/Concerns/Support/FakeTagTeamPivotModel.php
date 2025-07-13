<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Fake pivot model for testing CanJoinTagTeams trait in isolation.
 * This ensures trait tests are not coupled to real business models.
 */
class FakeTagTeamPivotModel extends Pivot
{
    protected $table = 'fake_tag_teams';

    protected $fillable = [
        'entity_id',
        'tag_team_id',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
