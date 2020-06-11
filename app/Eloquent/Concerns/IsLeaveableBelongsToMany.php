<?php

namespace App\Eloquent\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Relations\Pivot;

trait IsLeaveableBelongsToMany
{
    /**
     * The cached copy of the currently attached pivot models.
     *
     * @var Collection
     */
    private $currentlyAttached;

    public function detach($ids = null, $touch = true)
    {
        $query = $this->newPivotQuery();
        // If associated IDs were passed to the method we will only delete those
        // associations, otherwise all of the association ties will be broken.
        // We'll return the numbers of affected rows when we do the deletes.
        if (! is_null($ids)) {
            $ids = $this->parseIds($ids);
            if (empty($ids)) {
                return 0;
            }
            $query->whereIn($this->relatedPivotKey, (array) $ids);
        }

        $results = $query->update(['left_at' => now()]);
        if ($touch) {
            $this->touchIfTouching();
        }
        return $results;
    }

    /**
     * Sync the intermediate tables with a list of IDs or collection of models.
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array  $ids
     * @param  bool   $detaching
     * @return array
     */

    public function sync($ids, $detaching = true)
    {
        $changes = [
            'attached' => [], 'detached' => [], 'updated' => [],
        ];

        // First we need to attach any of the associated models that are not currently
        // in this joining table. We'll spin through the given IDs, checking to see
        // if they exist in the array of current ones, and if not we will insert.
        $current = $this->getCurrentlyAttachedPivots()
                        ->pluck($this->relatedPivotKey)->all();

        $detach = array_diff($current, array_keys(
            $records = $this->formatRecordsList($this->parseIds($ids))
        ));

        // Next, we will take the differences of the currents and given IDs and detach
        // all of the entities that exist in the "current" array but are not in the
        // array of the new IDs given to the method which will complete the sync.
        if ($detaching && count($detach) > 0) {
            $this->detach($detach);

            $changes['detached'] = $this->castKeys($detach);
        }

        // Now we are finally ready to attach the new records. Note that we'll disable
        // touching until after the entire operation is complete so we don't fire a
        // ton of touch operations until we are totally done syncing the records.
        $changes = array_merge(
            $changes,
            $this->attachNew($records, $current, false)
        );

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        if (count($changes['attached']) ||
            count($changes['updated'])) {
            $this->touchIfTouching();
        }

        return $changes;
    }

    /**
     * Get the pivot models that are currently attached.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCurrentlyAttachedPivots()
    {
        return $this->currentlyAttached ?: $this->newPivotQuery()->whereNull('left_at')->get()->map(function ($record) {
            $class = $this->using ? $this->using : Pivot::class;

            return (new $class)->setRawAttributes((array) $record, true);
        });
    }

    protected function baseAttachRecord($id, $timed)
    {
        return Arr::add(
            parent::baseAttachRecord($id, $timed),
            'joined_at',
            now()
        );
    }

    public function current()
    {
        // $this->wherePivot('left_at', null);
        // $this->wherePivot('left_at', DB::raw('is null'));
        // $this->wherePivot('left_at', 'is', DB::raw('null'));
        // $this->wherePivot('left_at', '==', DB::raw('is null'));
        $this->whereNull('left_at');

        return $this;
    }

    public function detached()
    {
        $this->wherePivot('left_at', '!=', null); //Laravel translates this to `IS NOT NULL`

        return $this;
    }

}
