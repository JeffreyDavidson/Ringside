<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Trait for repositories that manage member relationships with join/leave dates.
 *
 * This trait provides standardized methods for managing many-to-many relationships
 * with pivot table timestamps for tracking when members join and leave groups.
 * It handles complex membership scenarios where entities can join and leave
 * multiple times, maintaining historical records of all membership periods.
 *
 * The trait works with any models that have many-to-many relationships with
 * pivot tables containing joined_at and left_at timestamp columns.
 *
 * @template TGroup of \Illuminate\Database\Eloquent\Model
 * @template TMember of \Illuminate\Database\Eloquent\Model
 *
 * @see BelongsToMany For pivot table relationships
 *
 * @example
 * ```php
 * class StableRepository extends BaseRepository
 * {
 *     use ManagesMembers;
 *
 *     public function addWrestler(Stable $stable, Wrestler $wrestler, Carbon $joinDate): void
 *     {
 *         $this->addMember($stable, 'wrestlers', $wrestler, $joinDate);
 *     }
 *
 *     public function removeWrestler(Stable $stable, Wrestler $wrestler, Carbon $leaveDate): void
 *     {
 *         $this->removeCurrentMember($stable, 'wrestlers', $wrestler, $leaveDate);
 *     }
 * }
 * ```
 */
trait ManagesMembers
{
    /**
     * Add a member to a group with a join date.
     *
     * Creates a pivot table record linking the group to the member with a joined_at timestamp.
     * This method is typically used for many-to-many relationships like wrestlers joining stables.
     *
     * @param  Model  $group  The parent model (e.g., Stable, TagTeam)
     * @param  string  $relationship  The relationship method name (e.g., 'wrestlers', 'managers')
     * @param  Model  $member  The member being added (e.g., Wrestler, Manager)
     * @param  Carbon  $joinDate  The date when the member joins
     *
     * @throws QueryException If the relationship creation fails
     *
     * @example
     * ```php
     * $stable = Stable::find(1);
     * $wrestler = Wrestler::find(1);
     * $this->addMember($stable, 'wrestlers', $wrestler, now());
     * ```
     */
    protected function addMember(Model $group, string $relationship, Model $member, Carbon $joinDate): void
    {
        $group->{$relationship}()->attach($member->getKey(), [
            'joined_at' => $joinDate->toDateTimeString(),
        ]);
    }

    /**
     * Remove a member from a group with a leave date.
     *
     * Updates the existing pivot table record to set the left_at timestamp.
     * The member remains in the relationship history but is marked as having left.
     *
     * @param  Model  $group  The parent model (e.g., Stable, TagTeam)
     * @param  string  $relationship  The relationship method name (e.g., 'wrestlers', 'managers')
     * @param  Model  $member  The member being removed (e.g., Wrestler, Manager)
     * @param  Carbon  $leaveDate  The date when the member leaves
     *
     * @throws QueryException If the relationship update fails
     *
     * @example
     * ```php
     * $stable = Stable::find(1);
     * $wrestler = Wrestler::find(1);
     * $this->removeMember($stable, 'wrestlers', $wrestler, now());
     * ```
     */
    protected function removeMember(Model $group, string $relationship, Model $member, Carbon $leaveDate): void
    {
        $group->{$relationship}()->updateExistingPivot($member->getKey(), [
            'left_at' => $leaveDate->toDateTimeString(),
        ]);
    }

    /**
     * Remove a member from current relationships (where left_at is null).
     *
     * Updates only the active membership record (where left_at is null) to set the left_at timestamp.
     * This is useful when a member might have multiple historical relationships with the same group.
     *
     * @param  Model  $group  The parent model (e.g., Stable, TagTeam)
     * @param  string  $relationship  The relationship method name (e.g., 'wrestlers', 'managers')
     * @param  Model  $member  The member being removed (e.g., Wrestler, Manager)
     * @param  Carbon  $leaveDate  The date when the member leaves
     *
     * @throws QueryException If the relationship update fails
     *
     * @example
     * ```php
     * $tagTeam = TagTeam::find(1);
     * $wrestler = Wrestler::find(1);
     * $this->removeCurrentMember($tagTeam, 'wrestlers', $wrestler, now());
     * ```
     */
    protected function removeCurrentMember(Model $group, string $relationship, Model $member, Carbon $leaveDate): void
    {
        $group->{$relationship}()->wherePivotNull('left_at')->updateExistingPivot($member->getKey(), [
            'left_at' => $leaveDate->toDateTimeString(),
        ]);
    }
}
