<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasWrestlers
{
    /**
     * Get the wrestlers that have been tag team partners of the tag team.
     *
     * @return BelongsToMany<Wrestler>
     */
    public function wrestlers(): BelongsToMany
    {
        return $this->belongsToMany(Wrestler::class, 'tag_team_wrestler')
            ->withPivot('joined_at', 'left_at');
    }

    /**
     * Get current wrestlers of the tag team.
     *
     * @return BelongsToMany<Wrestler>
     */
    public function currentWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get previous tag team partners of the tag team.
     *
     * @return BelongsToMany<Wrestler>
     */
    public function previousWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the combined weight of both tag team partners in a tag team.
     *
     * @return Attribute<string, never>
     */
    public function combinedWeight(): Attribute
    {
        return new Attribute(
            get: fn () => $this->currentWrestlers->sum('weight')
        );
    }
}
