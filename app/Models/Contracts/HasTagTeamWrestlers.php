<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Defines the contract for models that manage tag team wrestler relationships.
 *
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 * @template TPivotModel of \Illuminate\Database\Eloquent\Relations\Pivot
 */
interface HasTagTeamWrestlers
{
    /**
     * Get all wrestlers associated with the tag team.
     *
     * @return BelongsToMany<Wrestler, TDeclaringModel, TPivotModel>
     */
    public function wrestlers(): BelongsToMany;

    /**
     * Get active wrestlers currently part of the tag team.
     *
     * @return BelongsToMany<Wrestler, TDeclaringModel, TPivotModel>
     */
    public function currentWrestlers(): BelongsToMany;

    /**
     * Get past wrestlers who previously were part of the tag team.
     *
     * @return BelongsToMany<Wrestler, TDeclaringModel, TPivotModel>
     */
    public function previousWrestlers(): BelongsToMany;
}
