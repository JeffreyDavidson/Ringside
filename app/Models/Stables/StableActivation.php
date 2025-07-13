<?php

declare(strict_types=1);

namespace App\Models\Stables;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $stable_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Stable $stable
 */
class StableActivation extends Model
{
    use HasFactory;

    protected $table = 'stables_activations';

    protected $fillable = [
        'stable_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Get the stable this activation belongs to.
     *
     * @return BelongsTo<Stable, static>
     */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }
}