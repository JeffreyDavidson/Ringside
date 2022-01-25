<?php

namespace App\Models;

use App\Builders\ManagerQueryBuilder;
use App\Enums\ManagerStatus;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\HasFullName;
use App\Models\Concerns\Manageables;
use App\Models\Concerns\OwnedByUser;
use App\Models\Contracts\CanBeAStableMember;
use App\Observers\ManagerObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends SingleRosterMember implements CanBeAStableMember
{
    use CanJoinStables,
        HasFactory,
        HasFullName,
        Manageables,
        OwnedByUser,
        SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['user_id', 'first_name', 'last_name', 'status'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => ManagerStatus::class,
    ];

    /**
     * The "boot" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        self::observe(ManagerObserver::class);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return \App\Builders\ManagerQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new ManagerQueryBuilder($query);
    }

    /**
     * Determine if the manager is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->currentEmployment()->exists();
    }
}
