<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @template TPivotModel of Pivot The pivot model for the manager relationship
 * @template TModel of Model The model that can be managed
 */
interface Manageable
{
    /**
     * @return BelongsToMany<Manager, TModel, TPivotModel>
     */
    public function managers(): BelongsToMany;

    /**
     * @return BelongsToMany<Manager, TModel, TPivotModel>
     */
    public function currentManagers(): BelongsToMany;

    /**
     * @return BelongsToMany<Manager, TModel, TPivotModel>
     */
    public function previousManagers(): BelongsToMany;
}
