<?php

declare(strict_types=1);

namespace App\Models\Stables;

use Database\Factories\Stables\StableActivityPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
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
 * @property-read Stable|null $stable
 *
 * @method static \Database\Factories\Stables\StableActivityPeriodFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableActivityPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableActivityPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableActivityPeriod query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(StableActivityPeriodFactory::class)]
class StableActivityPeriod extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stables_activations';

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
     * Get the stable associated with this activation.
     *
     * @return BelongsTo<Stable, $this>
     */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }
}
