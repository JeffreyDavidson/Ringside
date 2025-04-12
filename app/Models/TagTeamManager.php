<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $tag_team_id
 * @property int $manager_id
 * @property \Illuminate\Support\Carbon $hired_at
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamManager newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamManager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamManager query()
 *
 * @mixin \Eloquent
 */
final class TagTeamManager extends Pivot
{
    protected $table = 'tag_teams_managers';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hired_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
