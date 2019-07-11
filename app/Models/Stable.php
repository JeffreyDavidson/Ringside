<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stable extends Model
{
    use SoftDeletes;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->is_active = $model->started_at->lte(today());
        });
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
    protected $dates = ['started_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

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
     * Get all wrestlers that have been members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function wrestlers()
    {
        return $this->morphedByMany(Wrestler::class, 'member')->withPivot('left_at');
    }

    /**
     * Get all tag teams that have been members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function tagteams()
    {
        return $this->morphedByMany(TagTeam::class, 'member')->withPivot('left_at');
    }

    /**
     * Get all the members of the stable.
     *
     * @return Collection
     */
    public function getMembersAttribute()
    {
        return $this->wrestlers->merge($this->tagteams);
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
     * Add wrestlers to the stable.
     *
     * @param  array  $wrestlerIds
     * @return $this
     */
    public function addWrestlers($wrestlerIds)
    {
        foreach ($wrestlerIds as $wrestlerId) {
            $this->wrestlers()->attach($wrestlerId);
        }

        return $this;
    }

    /**
     * Add tag teams to the stable.
     *
     * @param  array  $tagteamIds
     * @return $this
     */
    public function addTagTeams($tagteamIds)
    {
        foreach ($tagteamIds as $tagteamId) {
            $this->tagteams()->attach($tagteamId);
        }

        return $this;
    }

    /**
     * Retire a stable.
     *
     * @return void
     */
    public function retire()
    {
        $this->retireStable();

        $this->wrestlers->each->retire();
        $this->tagteams->each->retire();

        return $this;
    }

    /**
     * Unretire a stable.
     *
     * @return void
     */
    public function unretire()
    {
        $this->unretireStable();

        $this->wrestlers->filter->isRetired()->each->unretire();
        $this->tagteams->filter->isRetired()->each->unretire();

        return $this;
    }
}
