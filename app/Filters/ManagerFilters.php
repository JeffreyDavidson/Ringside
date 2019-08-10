<?php

namespace App\Filters;

use Carbon\Carbon;

class ManagerFilters extends Filters
{
    /**
     * Registered filters to operate upon.
     *
     * @var array
     */
    protected $filters = ['status', 'started_at'];

    /**
     * Filter a query to include wrestlers of a status.
     *
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function status($status)
    {
        switch ($status) {
            case 'only_bookable':
                $this->builder->bookable();
                break;
            case 'only_pending_introduction':
                $this->builder->pendingIntroduction();
                break;
            case 'only_retired':
                $this->builder->retired();
                break;
            case 'only_suspended':
                $this->builder->suspended();
                break;
            case 'only_injured':
                $this->builder->injured();
                break;
        }

        return $this->builder;
    }

    /**
     * Filter a query to include managers of a specific date started.
     *
     * @param  array  $startedAt
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function started_at($startedAt)
    {
        if (isset($startedAt[0]) && !isset($startedAt[1])) {
            $this->builder->whereHas('employment', function ($query) use ($startedAt) {
                $query->whereDate('started_at', '=', Carbon::parse($startedAt[0])->toDateString());
            });
        } elseif (isset($startedAt[1])) {
            $this->builder->whereHas('employment', function ($query) use ($startedAt) {
                $query->whereBetween('started_at', [
                    Carbon::parse($startedAt[0])->toDateString(),
                    Carbon::parse($startedAt[1])->toDateString()
                ]);
            });
        }

        return $this->builder;
    }
}
