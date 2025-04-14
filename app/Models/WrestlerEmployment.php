<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $wrestler_id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TFactory|null $use_factory
 * @property-read Wrestler|null $wrestler
 *
 * @method static \Database\Factories\WrestlerEmploymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerEmployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerEmployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerEmployment query()
 *
 * @mixin \Eloquent
 */
final class WrestlerEmployment extends Model
{
    /** @use HasFactory<\Database\Factories\WrestlerEmploymentFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wrestlers_employments';

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
     * Get the employed model.
     *
     * @return BelongsTo<Wrestler, $this>
     */
    public function wrestler(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class);
    }
}
