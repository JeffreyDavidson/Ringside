<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Manager;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class WrestlerManager extends Pivot
{
    protected $table = 'wrestlers_managers';

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
     * @return BelongsTo<Manager, WrestlerManager>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    /**
     * @return BelongsTo<Wrestler, WrestlerManager>
     */
    public function wrestler(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class);
    }
}
