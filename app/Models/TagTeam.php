<?php

namespace App\Models;

use App\Traits\Hireable;
use App\Traits\Sluggable;
use App\Traits\Retireable;
use App\Traits\Activatable;
use App\Traits\Suspendable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends Model
{
    use SoftDeletes,
        Suspendable,
        Activatable,
        Hireable,
        Sluggable;

    use Retireable {
        Retireable::retire as private retireableRetire;
        Retireable::unretire as private retireableUnRetire;
    }

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
    protected $dates = ['hired_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the wrestlers belonging to the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wrestlers()
    {
        return $this->belongsToMany(Wrestler::class);
    }

    /**
     * Get the user belonging to the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the stables the tag team are members of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stables()
    {
        return $this->morphToMany(Stable::class, 'member');
    }

    /**
     * Get the current stable of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function stable()
    {
        return $this->morphToMany(Stable::class, 'member')->where('is_active', true);
    }

    /**
     * Add multiple wrestlers to a tag team.
     *
     * @param  array  $wrestlers
     * @return $this
     */
    public function addWrestlers($wrestlers)
    {
        $this->wrestlers()->attach($wrestlers);

        return $this;
    }

    /**
     * Scope a query to only include tag teams of a given state.
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

    /**
     * Retire a tag team.
     *
     * @return void
     */
    public function retire()
    {
        $this->retireableRetire();

        $this->wrestlers->each->retire();

        return $this;
    }

    /**
     * Unretire a tag team.
     *
     * @return void
     */
    public function unretire()
    {
        $this->retireableUnRetire();

        $this->wrestlers->filter->isRetired()->each->unretire();

        return $this;
    }

    /**
     * Get the combined weight of both wrestlers in a tag team.
     *
     * @return integer
     */
    public function getCombinedWeightAttribute()
    {
        return $this->wrestlers->sum('weight');
    }
}
