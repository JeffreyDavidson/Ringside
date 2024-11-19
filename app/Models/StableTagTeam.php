<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StableTagTeam extends Pivot
{
    protected $table = 'stables_tag_teams';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Stable, StableTagTeam>
     */
    public function stable(): BelongsTo
    {
        return $this->belongsTo(Stable::class);
    }

    /**
     * @return BelongsTo<TagTeam, StableTagTeam>
     */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }
}
