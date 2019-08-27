<?php

namespace App\Models;

use App\Enums\RefereeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Referee
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Employment $employment
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Employment[] $employments
 * @property-read bool $is_bookable
 * @property-read bool $is_employed
 * @property-read bool $is_injured
 * @property-read bool $is_retired
 * @property-read bool $is_suspended
 * @property-read \App\Enum\RefereeStatus $status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Injury[] $injuries
 * @property-read \App\Models\Injury $injury
 * @property-read \App\Models\Retirement $retirement
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Retirement[] $retirements
 * @property-read \App\Models\Suspension $suspension
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Suspension[] $suspensions
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee bookable()
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee injured()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Referee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee pendingIntroduced()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee retired()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee suspended()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Referee withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Referee withoutTrashed()
 * @mixin \Eloquent
 */
class Referee extends Model
{
    use SoftDeletes,
        Concerns\CanBeSuspended,
        Concerns\CanBeInjured,
        Concerns\CanBeRetired,
        Concerns\CanBeEmployed;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Determine the status of the referee.
     *
     * @return \App\Enum\RefereeStatus
     *
     */
    public function getStatusAttribute()
    {
        if ($this->is_bookable) {
            return RefereeStatus::BOOKABLE();
        }

        if ($this->is_retired) {
            return RefereeStatus::RETIRED();
        }

        if ($this->is_injured) {
            return RefereeStatus::INJURED();
        }

        if ($this->is_suspended) {
            return RefereeStatus::SUSPENDED();
        }

        return RefereeStatus::PENDING_INTRODUCTION();
    }

    /**
     * Determine if a referee is bookable.
     *
     * @return bool
     */
    public function getIsBookableAttribute()
    {
        return $this->is_employed && !($this->is_retired || $this->is_injured || $this->is_suspended);
    }

    /**
     * Get the full name of the referee.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Scope a query to only include bookable referees.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeBookable($query)
    {
        return $query->whereHas('employments', function (Builder $query) {
            $query->where('started_at', '<=', now())->whereNull('ended_at');
        })->whereDoesntHave('retirements', function (Builder $query) {
            $query->whereNull('ended_at');
        })->whereDoesntHave('injuries', function (Builder $query) {
            $query->whereNull('ended_at');
        })->whereDoesntHave('suspensions', function (Builder $query) {
            $query->whereNull('ended_at');
        });
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
        $data['name']         = $this->full_name;

        return $data;
    }
}
