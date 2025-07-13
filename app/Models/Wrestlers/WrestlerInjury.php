<?php

declare(strict_types=1);

namespace App\Models\Wrestlers;

use Database\Factories\Wrestlers\WrestlerInjuryFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wrestler_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wrestler|null $wrestler
 *
 * @method static \Database\Factories\Wrestlers\WrestlerInjuryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerInjury newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerInjury newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerInjury query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(WrestlerInjuryFactory::class)]
class WrestlerInjury extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wrestlers_injuries';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wrestler_id',
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
     * Get the wrestler associated with this injury.
     *
     * @return BelongsTo<Wrestler, $this>
     */
    public function wrestler(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class);
    }
}
