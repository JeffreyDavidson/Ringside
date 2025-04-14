<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \Illuminate\Support\Carbon $started_at
 * @property int $id
 * @property int $manager_id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TFactory|null $use_factory
 * @property-read Manager|null $manager
 *
 * @method static \Database\Factories\ManagerEmploymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerEmployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerEmployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerEmployment query()
 *
 * @mixin \Eloquent
 */
final class ManagerEmployment extends Model
{
    /** @use HasFactory<\Database\Factories\ManagerEmploymentFactory> */
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
     * @return BelongsTo<Manager, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }
}
