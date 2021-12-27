<?php

namespace App\Models;

use App\Builders\ManagerQueryBuilder;
use App\Enums\ManagerStatus;
use App\Models\Concerns\StableMember;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\SingleRosterMember;
use App\Observers\ManagerObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends SingleRosterMember implements CanBeAStableMember
{
    use HasFactory,
        HasFullName,
        Manageables,
        OwnedByUser,
        SoftDeletes,
        StableMember,
        Unguarded;

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
     * @return \Illuminate\Database\Eloquent\Builder|static
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
