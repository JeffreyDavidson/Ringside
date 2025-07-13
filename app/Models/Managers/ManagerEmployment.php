<?php

declare(strict_types=1);

namespace App\Models\Managers;

use Database\Factories\Managers\ManagerEmploymentFactory;
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
 * @property-read Manager|null $manager
 *
 * @method static \Database\Factories\Managers\ManagerEmploymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerEmployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerEmployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerEmployment query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(ManagerEmploymentFactory::class)]
class ManagerEmployment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'managers_employments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'manager_id',
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
     * Get the manager associated with this injury.
     *
     * @return BelongsTo<Manager, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }
}
