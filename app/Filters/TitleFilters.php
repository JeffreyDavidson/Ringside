<?php

namespace App\Filters;

use Carbon\Carbon;

class TitleFilters extends Filters
{
    /**
     * Registered filters to operate upon.
     *
     * @var array
     */
    protected $filters = ['status', 'introduced_at'];

    /**
     * Filter a query to include titles of a status.
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
        }

        return $this->builder;
    }

    /**
     * Filter a query to include titles of a specific date introduced.
     *
     * @param  array  $introduced
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function introduced_at($introducedAt)
    {
        if (isset($introducedAt[0]) && !isset($introducedAt[1])) {
            $this->builder->whereDate('introduced_at', '=', Carbon::parse($introducedAt[0])->toDateString());
        } elseif (isset($introducedAt[1])) {
            $this->builder->whereDate('introduced_at', '>=', Carbon::parse($introducedAt[0])->toDateString());
            $this->builder->whereDate('introduced_at', '<', Carbon::parse($introducedAt[1])->toDateString());
        }

        return $this->builder;
    }
}
