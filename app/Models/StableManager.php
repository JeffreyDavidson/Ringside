<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $stable_id
 * @property int $manager_id
 * @property \Illuminate\Support\Carbon $hired_at
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Manager $manager
 * @property-read \App\Models\Stable $stable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableManager newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableManager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableManager query()
 *
 * @mixin \Eloquent
 */
class StableManager extends Pivot
{
    protected $table = 'stables_managers';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hired_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Manager, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    /**
     * @return BelongsTo<Stable, $this>
     */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }
}
