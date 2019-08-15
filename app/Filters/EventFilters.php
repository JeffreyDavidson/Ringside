<?php

namespace App\Filters;

use Carbon\Carbon;
use Illuminate\Support\Str;

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
        if (method_exists($this->builder->getModel(), 'scope' . Str::studly($status))) {
            $this->builder->{Str::camel($status)}();
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
        if (isset($date[1])) {
            $this->builder->whereBetween('started_at', [
                Carbon::parse($date[0])->toDateString(),
                Carbon::parse($date[1])->toDateString()
            ]);
        } elseif (isset($date[0])) {
            $this->builder->whereDate('date', Carbon::parse($date[0])->toDateString());
        }

        return $this->builder;
    }
}
