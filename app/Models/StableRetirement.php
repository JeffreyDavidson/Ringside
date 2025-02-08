<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $stable_id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \App\Models\Stable|null $stable
 *
 * @method static \Database\Factories\StableRetirementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableRetirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableRetirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableRetirement query()
 *
 * @mixin \Eloquent
 */
class StableRetirement extends Model
{
    /** @use HasFactory<\Database\Factories\StableRetirementFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stables_retirements';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stable_id',
        'started_at',
        'ended_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Stable, $this>
     */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }
}
