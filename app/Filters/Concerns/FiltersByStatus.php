<?php

namespace App\Filters\Concerns;

use Illuminate\Support\Str;

trait FiltersByStatus
{
    /**
     * Filter a query to include models of a status.
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
}
