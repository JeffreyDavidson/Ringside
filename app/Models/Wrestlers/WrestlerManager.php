<?php

declare(strict_types=1);

namespace App\Models\Wrestlers;

use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wrestler_id
 * @property int $manager_id
 * @property Carbon $hired_at
 * @property Carbon|null $fired_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manager $manager
 * @property-read Wrestler $wrestler
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerManager newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerManager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerManager query()
 *
 * @mixin \Eloquent
 */
class WrestlerManager extends Pivot
{
    protected $table = 'wrestlers_managers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wrestler_id',
        'manager_id',
        'hired_at',
        'fired_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hired_at' => 'datetime',
            'fired_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Manager, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    /**
     * @return BelongsTo<Wrestler, $this>
     */
    public function wrestler(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class);
    }
}
