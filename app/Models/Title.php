<?php

namespace App\Models;

use App\Enums\TitleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * \App\Models\Title
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon $introduced_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Retirement $previousRetirement
 * @property-read \App\Models\Retirement $retirement
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Retirement[] $retirements
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Title onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title retired()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title whereIntroducedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Title whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Title withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Title withoutTrashed()
 * @mixin \Eloquent
 */
class Title extends Model
{
    use SoftDeletes;

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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_bookable'];

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
    public function retirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')->whereNull('ended_at');
    }

    /**
     * Determine the status of the title.
     *
     * @return \App\Enum\TitleStatus
     *
     */
    public function getStatusAttribute()
    {
        if ($this->is_bookable) {
            return TitleStatus::BOOKABLE();
        }

        if ($this->is_retired) {
            return TitleStatus::RETIRED();
        }

        return TitleStatus::PENDING_INTRODUCTION();
    }

    /**
     * Determine if a title is has been introduced.
     *
     * @return bool
     */
    public function getIsPendingIntroductionAttribute()
    {
        return is_null($this->introduced_at) || $this->introduced_at->isFuture();
    }

    /**
     * Determine if a title is usuable.
     *
     * @return bool
     */
    public function getIsBookableAttribute()
    {
        return !($this->is_retired || $this->is_pending_introduction);
    }

    /**
     * Determine if a title is retired.
     *
     * @return bool
     */
    public function getIsRetiredAttribute()
    {
        return $this->retirements()->whereNull('ended_at')->exists();
    }

    /**
     * Retrieve a formatted introduced at date timestamp.
     *
     * @return string
     */
    public function getFormattedIntroducedAtAttribute()
    {
        return $this->introduced_at->toDateString();
    }

    /**
     * Scope a query to only include active titles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeBookable($query)
    {
        $query->whereDoesntHave('retirements', function (Builder $query) {
            $query->whereNull('ended_at');
        });
        $query->whereNotNull('introduced_at');
        $query->where('introduced_at', '<=', now());
    }

    /**
     * Scope a query to only include retired titles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired($query)
    {
        return $query->whereHas('retirements', function ($query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Scope a query to only include pending introduced titles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePendingIntroduced($query)
    {
        $query->where('introduced_at', '>', now());
    }

    /**
     * Activate a title.
     *
     * @return \App\Models\Title $this
     */
    public function activate()
    {
        $this->update(['introduced_at' => now()]);

        return $this;
    }

    /**
     * Retire a title.
     *
     * @return \App\Models\Retirement
     */
    public function retire()
    {
        $this->retirements()->create(['started_at' => now()]);
    }

    /**
     * Unretire a title.
     *
     * @return bool
     */
    public function unretire()
    {
        return $this->retirement()->update(['ended_at' => now()]);
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $data                 = parent::toArray();
        $data['status']       = $this->status->label();

        return $data;
    }
}
