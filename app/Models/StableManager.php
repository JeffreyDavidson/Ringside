<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

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
     * @return BelongsTo<Manager, StableManager>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    /**
     * @return BelongsTo<Stable, StableManager>
     */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }
}
