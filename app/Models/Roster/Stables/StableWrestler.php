<?php

declare(strict_types=1);

namespace App\Models\Roster\Stables;

use App\Builders\Roster\StableMembershipBuilder;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $stable_id
 * @property int $wrestler_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Stable $stable
 * @property-read Wrestler|null $wrestler
 *
 * @method static StableMembershipBuilder<static> current()
 * @method static StableMembershipBuilder<static> ended()
 * @method static StableMembershipBuilder<static> forStableId(int $stableId)
 * @method static StableMembershipBuilder<static> mostRecentlyJoinedFirst()
 * @method static StableMembershipBuilder<static> newModelQuery()
 * @method static StableMembershipBuilder<static> newQuery()
 * @method static StableMembershipBuilder<static> query()
 *
 * @mixin \Eloquent
 */
#[Fillable('stable_id', 'wrestler_id', 'joined_at', 'left_at')]
#[UseEloquentBuilder(StableMembershipBuilder::class)]
class StableWrestler extends Pivot
{
    /** @var string */
    protected $table = 'stables_wrestlers';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Stable, $this> */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }

    /** @return BelongsTo<Wrestler, $this> */
    public function wrestler(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class);
    }
}
