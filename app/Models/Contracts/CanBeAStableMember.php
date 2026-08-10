<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @template TPivotModel of Pivot The pivot model for the stable relationship
 * @template TModel of Model The model that can be a stable member
 *
 * @see CanJoinStables For the trait implementation
 */
interface CanBeAStableMember
{
    /**
     * @return BelongsToMany<Stable, TModel, TPivotModel>
     */
    public function stables(): BelongsToMany;

    public function currentStable(): BelongsToOne;
}
