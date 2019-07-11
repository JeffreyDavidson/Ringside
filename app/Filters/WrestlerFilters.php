<?php

namespace App\Filters;

use Carbon\Carbon;

class WrestlerFilters extends Filters
{
    /**
     * Registered filters to operate upon.
     *
     * @var array
     */
    protected $filters = ['status', 'hired_at'];

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
            case 'only_inactive':
                $this->builder->inactive();
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
     * Filter a query to include wrestlers of a specific date hired.
     *
     * @param  array  $hiredAt
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function hired_at($hiredAt)
    {
        if (isset($hiredAt[0]) && !isset($hiredAt[1])) {
            $this->builder->whereDate('hired_at', '=', Carbon::parse($hiredAt[0])->toDateString());
        } elseif (isset($hiredAt[1])) {
            $this->builder->whereDate('hired_at', '>=', Carbon::parse($hiredAt[0])->toDateString());
            $this->builder->whereDate('hired_at', '<', Carbon::parse($hiredAt[1])->toDateString());
        }

        return $this->builder;
    }
}
