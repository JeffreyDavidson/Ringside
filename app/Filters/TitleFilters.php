<?php

namespace App\Filters;

use Carbon\Carbon;
use Illuminate\Support\Str;

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
        if (method_exists($this->builder->getModel(), 'scope' . Str::studly($status))) {
            $this->builder->{Str::camel($status)}();
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
        if (isset($introducedAt[1])) {
            $this->builder->whereBetween('started_at', [
                Carbon::parse($introducedAt[0])->toDateString(),
                Carbon::parse($introducedAt[1])->toDateString()
            ]);
        } elseif (isset($introducedAt[0])) {
            $this->builder->whereDate('date', Carbon::parse($introducedAt[0])->toDateString());
        }

        return $this->builder;
    }
}
