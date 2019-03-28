<?php

namespace App\Traits;

trait Hireable
{
    /**
     * Boot the trait for every model.
     *
     * @return void
     */
    public static function bootHireable()
    {
        static::creating(function ($model) {
            $model->is_active = $model->hired_at->lte(today());
        });
    }
}
