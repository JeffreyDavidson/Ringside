<?php

namespace App\Filters;

use Carbon\Carbon;

class RefereeFilters extends Filters
{
    /**
     * Registered filters to operate upon.
     *
     * @var array
     */
    protected $filters = ['status', 'hired_at'];

    /**
     * Filter a query to include referees of a status.
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
                $this->builder->pendingIntroduced();
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
     * Filter a query to include referees of a specific started at date.
     *
     * @param  array  $startedAt
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function started_at($startedAt)
    {
        if (isset($startedAt[0]) && !isset($startedAt[1])) {
            $this->builder->employments()->whereDate('started_at', '=', Carbon::parse($startedAt[0])->toDateString());
        } elseif (isset($startedAt[1])) {
            $this->builder->employments()->whereDate('started_at', '>=', Carbon::parse($startedAt[0])->toDateString());
            $this->builder->employments()->whereDate('started_at', '<', Carbon::parse($startedAt[1])->toDateString());
        }

        return $this->builder;
    }
}
