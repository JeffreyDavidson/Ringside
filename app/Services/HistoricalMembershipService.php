<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HistoricalMembershipService
{
    /**
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     * @template TPivotModel of Pivot
     *
     * @param  BelongsToMany<TRelatedModel, TDeclaringModel, TPivotModel>  $relationship
     * @param  Collection<int, TRelatedModel>|null  $members
     */
    public function add(
        BelongsToMany $relationship,
        ?Collection $members,
        Carbon $date,
    ): void {
        if ($members === null || $members->isEmpty()) {
            return;
        }

        $relationship->attach($members->map(
            fn (Model $member): int => Arr::integer(['key' => $member->getKey()], 'key'),
        )->all(), [
            'joined_at' => $date,
            'left_at' => null,
        ]);
    }

    /**
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     * @template TPivotModel of Pivot
     *
     * @param  BelongsToMany<TRelatedModel, TDeclaringModel, TPivotModel>  $relationship
     * @param  Collection<int, TRelatedModel>|null  $members
     */
    public function remove(
        BelongsToMany $relationship,
        ?Collection $members,
        Carbon $date,
    ): void {
        if ($members === null || $members->isEmpty()) {
            return;
        }

        foreach ($members as $member) {
            $relationship->newPivotStatementForId($member->getKey())
                ->whereNull('left_at')
                ->update(['left_at' => $date]);
        }
    }

    /**
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     * @template TPivotModel of Pivot
     *
     * @param  BelongsToMany<TRelatedModel, TDeclaringModel, TPivotModel>  $relationship
     * @param  Collection<int, TRelatedModel>  $currentMembers
     * @param  Collection<int, TRelatedModel>|null  $desiredMembers
     */
    public function synchronize(
        BelongsToMany $relationship,
        Collection $currentMembers,
        ?Collection $desiredMembers,
        Carbon $date,
    ): void {
        if ($desiredMembers === null) {
            return;
        }

        $this->remove($relationship, $currentMembers->diff($desiredMembers), $date);
        $this->add($relationship, $desiredMembers->diff($currentMembers), $date);
    }
}
