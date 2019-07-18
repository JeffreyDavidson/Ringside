<?php

namespace App\Filters;

use Carbon\Carbon;

class TagTeamFilters extends Filters
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
            case 'only_pending_introduced':
                $this->builder->pendingIntroduced();
                break;
            case 'only_retired':
                $this->builder->retired();
                break;
            case 'only_suspended':
                $this->builder->suspended();
                break;
        }

        return $this->builder;
    }

    /**
     * Filter a query to include wrestlers of a specific date started.
     *
     * @param  array  $startedAt
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function started_at($startedAt)
    {
        if (isset($startedAt[0]) && !isset($startedAt[1])) {
            $this->builder->whereDate('started_at', '=', Carbon::parse($startedAt[0])->toDateString());
        } elseif (isset($startedAt[1])) {
            $this->builder->whereDate('started_at', '>=', Carbon::parse($startedAt[0])->toDateString());
            $this->builder->whereDate('started_at', '<', Carbon::parse($startedAt[1])->toDateString());
        }

        return $this->builder;
    }
}
