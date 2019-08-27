<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Injury extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The relationships that should be touched on save.
     *
     * @var array
     */
    protected $touches = ['injurable'];

    /**
     * Retrieve the injured model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function injurable()
    {
        return $this->morphTo();
    }
}
