<?php

namespace App\Models;

use App\Builders\RefereeQueryBuilder;
use App\Enums\RefereeStatus;
use App\Models\Concerns\HasFullName;
use App\Models\Contracts\Bookable;
use App\Observers\RefereeObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referee extends SingleRosterMember implements Bookable
{
    use HasFactory,
        HasFullName,
        SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['first_name', 'last_name', 'status'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => RefereeStatus::class,
    ];

    /**
     * The "boot" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        self::observe(RefereeObserver::class);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return \App\Builders\RefereeQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new RefereeQueryBuilder($query);
    }
}
