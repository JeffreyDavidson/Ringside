<?php

namespace App\Filters;

use Carbon\Carbon;
use Illuminate\Support\Str;

class WrestlerFilters extends Filters
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
        if (method_exists($this->builder->getModel(), 'scope' . Str::studly($status))) {
            $this->builder->{Str::camel($status)}();
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
        if (isset($startedAt[1])) {
            $this->builder->whereHas('employment', function ($query) use ($startedAt) {
                $query->whereBetween('started_at', [
                    Carbon::parse($startedAt[0])->toDateString(),
                    Carbon::parse($startedAt[1])->toDateString()
                ]);
            });
        } elseif (isset($startedAt[0])) {
            $this->builder->whereHas('employment', function ($query) use ($startedAt) {
                $query->whereDate('started_at', Carbon::parse($startedAt[0])->toDateString());
            });
        }

        return $this->builder;
    }
}
