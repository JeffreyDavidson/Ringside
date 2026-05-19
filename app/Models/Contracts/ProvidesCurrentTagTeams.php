<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Marker interface for models that provide access to current tag teams.
 *
 * This interface defines a minimal contract for any model that needs to expose
 * a currentTagTeams() relationship method. This allows type-safe polymorphic
 * access to tag team collections.
 *
 * This interface is used to replace method_exists() calls with proper type checking:
 * - Instead of: method_exists($entity, 'currentTagTeams')
 * - Use: $entity instanceof ProvidesCurrentTagTeams
 *
 * Currently, this is primarily implemented by Stables that have tag team members.
 *
 * @example
 * ```php
 * // Type-safe polymorphic access
 * if ($entity instanceof ProvidesCurrentTagTeams) {
 *     $tagTeams = $entity->currentTagTeams()->get();
 *     foreach ($tagTeams as $tagTeam) {
 *         // Process tag team...
 *     }
 * }
 * ```
 */
interface ProvidesCurrentTagTeams
{
    /**
     * Get tag teams currently associated with this entity.
     *
     * For Stables, this returns current tag team members.
     *
     * @return BelongsToMany<TagTeam, Model>
     *                                       A relationship instance for accessing current tag teams
     */
    public function currentTagTeams(): BelongsToMany;
}
