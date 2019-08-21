<?php

namespace App\Filters\Concerns;

use Carbon\Carbon;

trait FiltersByStartDate
{
    /**
     * Filter a query to include models of a specific date started.
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
