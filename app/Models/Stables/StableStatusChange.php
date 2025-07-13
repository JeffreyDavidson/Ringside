<?php

declare(strict_types=1);

namespace App\Models\Stables;

use App\Enums\Shared\ActivationStatus;
use Database\Factories\Stables\StableStatusChangeFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Stable status change model for tracking status history.
 *
 * @property int $id
 * @property int $stable_id
 * @property ActivationStatus $status
 * @property Carbon $changed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Stable $stable
 *
 * @method static \Database\Factories\Stables\StableStatusChangeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableStatusChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableStatusChange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableStatusChange query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(StableStatusChangeFactory::class)]
class StableStatusChange extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stable_id',
        'status',
        'changed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ActivationStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    /**
     * Get the stable that owns this status change.
     *
     * @return BelongsTo<Stable, StableStatusChange>
     */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }
}
