<?php

namespace App\Models;

use App\Traits\Sluggable;
use App\Traits\Retireable;
use App\Traits\Activatable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Title extends Model
{
    use Sluggable,
        Activatable,
        Retireable,
        SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['introduced_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->is_active = $model->introduced_at->lte(today());
        });
    }

    /**
     * Scope a query to only include titles of a given state.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasState($query, $state)
    {
        $scope = 'scope' . Str::studly($state);

        if (method_exists($this, $scope)) {
            return $this->{$scope}($query);
        }
    }
}
