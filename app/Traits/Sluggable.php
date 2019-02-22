<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Sluggable
{
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function bootSluggable()
    {
        static::creating(function ($model) {
            $model->slug = Str::slug($model->name);
        });
    }
}
