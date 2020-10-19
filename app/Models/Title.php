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
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($title) {
            $title->updateStatus();
        });
    }

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
     * @return void
     */
    public function retire($retiredAt = null)
    {
        throw_unless($this->canBeRetired(), new CannotBeRetiredException('Entity cannot be retired. This entity does not have an active activation.'));

        $retiredDate = $retiredAt ?: now();

        $this->currentActivation()->update(['ended_at' => $retiredDate]);
        $this->retirements()->create(['started_at' => $retiredDate]);
        $this->updateStatusAndSave();
    }

    /**
     * Unretire a title.
     *
     * @param  string|null $startedAt
     * @return void
     */
    public function unretire($unretiredAt = null)
    {
        throw_unless($this->canBeUnretired(), new CannotBeUnretiredException('Entity cannot be unretired. This entity is not retired.'));

        $unretiredDate = $unretiredAt ?: now();

        $this->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $this->activate($unretiredDate);
        $this->updateStatusAndSave();
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
            // throw new CannotBeRetiredException('Entity cannot be retired. This entity does not have an active activation.');
            return false;
        }

        if ($this->isRetired()) {
            // throw new CannotBeRetiredException('Entity cannot be retired. This entity is retired.');
            return false;
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
            // throw new CannotBeUnretiredException('Entity cannot be unretired. This entity is not retired.');
            return false;
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

    /**
     * Update the status for the title.
     *
     * @return void
     */
    public function updateStatus()
    {
        if ($this->isCurrentlyActivated()) {
            $this->status = TitleStatus::ACTIVE;
        } elseif ($this->hasFutureActivation()) {
            $this->status = TitleStatus::FUTURE_ACTIVATION;
        } elseif ($this->isDeactivated()) {
            $this->status = TitleStatus::INACTIVE;
        } elseif ($this->isRetired()) {
            $this->status = TitleStatus::RETIRED;
        } else {
            $this->status = TitleStatus::UNACTIVATED;
        }
    }

    /**
     * Updates a title's status and saves.
     *
     * @return void
     */
    public function updateStatusAndSave()
    {
        $this->updateStatus();
        $this->save();
    }
}
