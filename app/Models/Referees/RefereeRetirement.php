<?php

declare(strict_types=1);

namespace App\Models\Referees;

use Database\Factories\Referees\RefereeRetirementFactory;
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
 * @method static \Database\Factories\Referees\RefereeRetirementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeRetirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeRetirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeRetirement query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(RefereeRetirementFactory::class)]
class RefereeRetirement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'referees_retirements';

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
     * Get the referee associated with this retirement.
     *
     * @return BelongsTo<Referee, $this>
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }
}
