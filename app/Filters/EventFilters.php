<?php

namespace App\Filters;

use Carbon\Carbon;

class EventFilters extends Filters
{
    /**
     * Registered filters to operate upon.
     *
     * @var array
     */
    protected $filters = ['status', 'date'];

    /**
     * Filter a query to include events of a status.
     *
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function status($status)
    {
        switch ($status) {
            case 'only_scheduled':
                $this->builder->scheduled();
                break;
            case 'only_past':
                $this->builder->past();
                break;
        }

        return $this->builder;
    }

    /**
     * Filter a query to include events of a specific date.
     *
     * @param  array  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function date($date)
    {
        if (isset($date[0]) && !isset($date[1])) {
            $this->builder->whereDate('date', '=', Carbon::parse($date[0])->toDateString());
        } elseif (isset($date[1])) {
            $this->builder->whereDate('date', '>=', Carbon::parse($date[0])->toDateString());
            $this->builder->whereDate('date', '<', Carbon::parse($date[1])->toDateString());
        }

        return $this->builder;
    }
}
