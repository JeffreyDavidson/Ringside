<?php

namespace App\Models;

use App\Enums\TitleStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeUnretiredException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Title extends Model
{
    use SoftDeletes,
        HasFactory,
        Concerns\CanBeActivated,
        Concerns\Unguarded;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => TitleStatus::class,
    ];

    /**
     * Get the retirements of the title.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the title.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentRetirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the previous retirements of the title.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousRetirements()
    {
        return $this->retirements()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous retirement of the title.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousRetirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include retired titles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired($query)
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to only include retired titles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithCurrentRetiredAtDate($query)
    {
        return $query->addSelect(['current_retired_at' => Retirement::select('started_at')
            ->whereColumn('retiree_id', $this->getTable().'.id')
            ->where('retiree_type', $this->getMorphClass())
            ->oldest('started_at')
            ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the title's current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByCurrentRetiredAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_retired_at) $direction");
    }

    /**
     * Retire a title.
     *
     * @param  string|null $retiredAt
     * @return $this
     */
    public function retire($retiredAt = null)
    {
        if ($this->canBeRetired()) {
            $retiredDate = $retiredAt ?: now();

            $this->currentActivation()->update(['ended_at' => $retiredDate]);
            $this->retirements()->create(['started_at' => $retiredDate]);

            $this->touch();

            return $this;
        }
    }

    /**
     * Unretire a title.
     *
     * @param  string|null $startedAt
     * @return $this
     */
    public function unretire($unretiredAt = null)
    {
        if ($this->canBeUnretired()) {
            $unretiredDate = $unretiredAt ?: now();

            $this->currentRetirement()->update(['ended_at' => $unretiredDate]);
            $this->activate($unretiredDate);

            $this->touch();

            return $this;
        }
    }

    /**
     * Check to see if the title is retired.
     *
     * @return bool
     */
    public function isRetired()
    {
        return $this->whereHas('currentRetirement')->exists();
    }

    /**
     * Determine if the title can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if ($this->isUnactivated() || $this->isDeactivated() || $this->hasFutureActivation()) {
            throw new CannotBeRetiredException('Entity cannot be retired. This entity does not have an active activation.');
        }

        if ($this->isRetired()) {
            throw new CannotBeRetiredException('Entity cannot be retired. This entity is retired.');
        }

        return true;
    }

    /**
     * Determine if the title can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        if (! $this->isRetired()) {
            throw new CannotBeUnretiredException('Entity cannot be unretired. This entity is not retired.');
        }

        return true;
    }

    /**
     * Scope a query to only include competable titles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompetable($query)
    {
        return $query->where('status', TitleStatus::ACTIVE);
    }

    /**
     * Check to see if the model can be competed for.
     *
     * @return bool
     */
    public function isCompetable()
    {
        if ($this->isUnactivated() || $this->isDeactivated() || $this->isRetired() || $this->hasFutureActivation()) {
            return false;
        }

        return true;
    }
}
