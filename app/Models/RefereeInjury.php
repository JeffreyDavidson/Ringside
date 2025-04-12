<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $referee_id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TFactory|null $use_factory
 * @property-read Referee|null $referee
 *
 * @method static \Database\Factories\RefereeInjuryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeInjury newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeInjury newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeInjury query()
 *
 * @mixin \Eloquent
 */
final class RefereeInjury extends Model
{
    /** @use HasFactory<\Database\Factories\RefereeInjuryFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'referees_injuries';

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
     * @return BelongsTo<Referee, $this>
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }
}
