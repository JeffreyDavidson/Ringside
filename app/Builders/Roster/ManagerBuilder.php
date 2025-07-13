<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\Managers\Manager;

/**
 * Custom query builder for the Manager model.
 *
 * Provides specialized query methods for filtering managers by their employment status,
 * including available, released, injured, unemployed, retired, suspended, and future
 * employed managers. This builder enables easy filtering of managers based on their
 * current availability and employment conditions.
 *
 * @template TModel of Manager
 *
 * @extends SingleRosterMemberBuilder<TModel>
 *
 * @example
 * ```php
 * // Get all available managers
 * $availableManagers = Manager::query()->available()->get();
 *
 * // Get injured managers who need to be cleared
 * $injuredManagers = Manager::query()->injured()->get();
 *
 * // Chain conditions for complex queries
 * $activeManagers = Manager::query()
 *     ->available()
 *     ->whereHas('wrestlers')
 *     ->get();
 * ```
 */
class ManagerBuilder extends SingleRosterMemberBuilder
{
    // Managers don't need booking scopes - they are not booked for matches
}
