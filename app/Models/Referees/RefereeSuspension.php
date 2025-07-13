<?php

declare(strict_types=1);

namespace App\Models\Referees;

use Database\Factories\Referees\RefereeSuspensionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $referee_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Referee|null $referee
 *
 * @method static \Database\Factories\Referees\RefereeSuspensionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeSuspension query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(RefereeSuspensionFactory::class)]
class RefereeSuspension extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'referees_suspensions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'referee_id',
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
     * Get the referee associated with this suspension.
     *
     * @return BelongsTo<Referee, $this>
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }
}
