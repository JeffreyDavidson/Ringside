<?php

declare(strict_types=1);

namespace App\Models\Managers;

use Database\Factories\Managers\ManagerSuspensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $manager_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Manager|null $manager
 *
 * @method static \Database\Factories\Managers\ManagerSuspensionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerSuspension query()
 *
 * @mixin \Eloquent
 */
#[Table('managers_suspensions')]
#[Fillable('manager_id', 'started_at', 'ended_at')]
#[UseFactory(ManagerSuspensionFactory::class)]
class ManagerSuspension extends Model
{
    /** @use HasFactory<ManagerSuspensionFactory> */
    use HasFactory;

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
     * Get the manager associated with this suspension.
     *
     * @return BelongsTo<Manager, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }
}
