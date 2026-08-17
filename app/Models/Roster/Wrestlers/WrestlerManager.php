<?php

declare(strict_types=1);

namespace App\Models\Roster\Wrestlers;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Models\Roster\Managers\Manager;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
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
 *
 * @property-read Manager $manager
 * @property-read Wrestler $wrestler
 *
 * @method static ManagerAssignmentBuilder<static> current()
 * @method static ManagerAssignmentBuilder<static> ended()
 * @method static ManagerAssignmentBuilder<static> forManagerId(int $managerId)
 * @method static ManagerAssignmentBuilder<static> mostRecentlyHiredFirst()
 * @method static ManagerAssignmentBuilder<static> newModelQuery()
 * @method static ManagerAssignmentBuilder<static> newQuery()
 * @method static ManagerAssignmentBuilder<static> query()
 *
 * @mixin \Eloquent
 */
#[Fillable('wrestler_id', 'manager_id', 'hired_at', 'fired_at')]
#[UseEloquentBuilder(ManagerAssignmentBuilder::class)]
#[Table(name: 'wrestlers_managers')]
class WrestlerManager extends Pivot
{
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
