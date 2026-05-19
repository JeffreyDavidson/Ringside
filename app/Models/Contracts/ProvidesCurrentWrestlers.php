<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Marker interface for models that provide access to current wrestlers.
 *
 * This interface defines a minimal contract for any model that needs to expose
 * a currentWrestlers() relationship method. This allows type-safe polymorphic
 * access to wrestler collections across different entity types.
 *
 * This interface is used to replace method_exists() calls with proper type checking:
 * - Instead of: method_exists($entity, 'currentWrestlers')
 * - Use: $entity instanceof ProvidesCurrentWrestlers
 *
 * Different entities implement this for different relationship types:
 * - TagTeam: currentWrestlers() returns tag team partners
 * - Stable: currentWrestlers() returns stable member wrestlers
 * - Manager: currentWrestlers() returns managed wrestlers
 *
 * @example
 * ```php
 * // Type-safe polymorphic access
 * if ($entity instanceof ProvidesCurrentWrestlers) {
 *     $wrestlers = $entity->currentWrestlers()->get();
 *     foreach ($wrestlers as $wrestler) {
 *         // Process wrestler...
 *     }
 * }
 * ```
 */
interface ProvidesCurrentWrestlers
{
    /**
     * Get wrestlers currently associated with this entity.
     *
     * The specific meaning of "current" depends on the implementing entity:
     * - For TagTeams: Active tag team partners
     * - For Stables: Current stable member wrestlers
     * - For Managers: Currently managed wrestlers
     *
     * @return BelongsToMany<Wrestler, Model>
     *                                        A relationship instance for accessing current wrestlers
     */
    public function currentWrestlers(): BelongsToMany;
}
