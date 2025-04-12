<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $stable_id
 * @property int $wrestler_id
 * @property \Illuminate\Support\Carbon $joined_at
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableWrestler newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableWrestler newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableWrestler query()
 *
 * @mixin \Eloquent
 */
final class StableWrestler extends Pivot
{
    protected $table = 'stables_wrestlers';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
