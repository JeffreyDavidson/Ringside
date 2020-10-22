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
        Concerns\Retirable,
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
     * Retire a title.
     *
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire($retiredAt = null)
    {
        throw_unless($this->canBeRetired(), new CannotBeRetiredException);

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
        throw_unless($this->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = $unretiredAt ?: now();

        $this->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $this->activate($unretiredDate);
        $this->updateStatusAndSave();
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
