<?php

namespace App\Models;

use App\Enums\WrestlerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Eloquent\Concerns\HasCustomRelationships;

/**
 * App\Models\Wrestler
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property int $height
 * @property int $weight
 * @property string $hometown
 * @property string|null $signature_move
 * @property \Illuminate\Support\Carbon $hired_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string $feet
 * @property-read string $formatted_height
 * @property-read string $formatted_hired_at
 * @property-read string $inches
 * @property-read bool $is_bookable
 * @property-read bool $is_hired
 * @property-read bool $is_injured
 * @property-read bool $is_retired
 * @property-read bool $is_suspended
 * @property-read \App\Enum\WrestlerStatus $status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Injury[] $injuries
 * @property-read \App\Models\Injury $injury
 * @property-read \App\Models\Retirement $retirement
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Retirement[] $retirements
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stable[] $stable
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stable[] $stables
 * @property-read \App\Models\Suspension $suspension
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Suspension[] $suspensions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TagTeam[] $tagteam
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TagTeam[] $tagteams
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler bookable()
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler pending_introduced()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler injured()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Wrestler onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler retired()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler suspended()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereHiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereHometown($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereSignatureMove($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Wrestler whereWeight($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Wrestler withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Wrestler withoutTrashed()
 * @mixin \Eloquent
 */
class Wrestler extends Model
{
    use SoftDeletes, HasCustomRelationships;


    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the user belonging to the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tag teams the wrestler has belonged to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tagteams()
    {
        return $this->belongsToMany(TagTeam::class);
    }

    /**
     * Get the current tag team of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tagteam()
    {
        return $this->belongsToMany(TagTeam::class)->whereHas('employments', function (Builder $query) {
            $query->where('started_at', '<=', now());
        });
    }

    /**
     * Get the stables the wrestler is a member of.
     *
     * @return \App\Eloquent\Relationships\LeaveableMorphToMany
     */
    public function stables()
    {
        return $this->leaveableMorphToMany(Stable::class, 'member')->using(Member::class);
    }

    /**
     * Get the current stable of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function stable()
    {
        // return $this->morphToMany(Stable::class, 'member')->where('is_active', true);
    }

    /**
     * Get the retirements of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function retirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')->whereNull('ended_at');
    }

    /**
     * Get the suspensions of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function suspensions()
    {
        return $this->morphMany(Suspension::class, 'suspendable');
    }

    /**
     * Get the current suspension of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function suspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')->whereNull('ended_at');
    }

    /**
     * Get the injuries of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function injuries()
    {
        return $this->morphMany(Injury::class, 'injurable');
    }

    /**
     * Get the current injury of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function injury()
    {
        return $this->morphOne(Injury::class, 'injurable')->whereNull('ended_at');
    }

    /**
     * Get all of the employments of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function employments()
    {
        return $this->morphMany(Employment::class, 'employable')->whereNull('ended_at');
    }

    /**
     * Get the current employment of the wrestler.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function employment()
    {
        return $this->morphOne(Employment::class, 'employable')->whereNull('ended_at');
    }

    /**
     * Return the wrestler's height formatted.
     *
     * @return string
     */
    public function getFormattedHeightAttribute()
    {
        $feet = floor($this->height / 12);
        $inches = ($this->height % 12);

        return $feet . '\'' . $inches . '"';
    }

    /**
     * Return the wrestler's hired at date formatted.
     *
     * @return string
     */
    public function getFormattedStartedAtAttribute()
    {
        return $this->employments()->latest()->first()->started_at->format('M d, Y');
    }

    /**
     * Determine the status of the wrestler.
     *
     * @return \App\Enum\WrestlerStatus
     *
     */
    public function getStatusAttribute()
    {
        if ($this->is_bookable) {
            return WrestlerStatus::BOOKABLE();
        }

        if ($this->is_retired) {
            return WrestlerStatus::RETIRED();
        }

        if ($this->is_injured) {
            return WrestlerStatus::INJURED();
        }

        if ($this->is_suspended) {
            return WrestlerStatus::SUSPENDED();
        }

        return WrestlerStatus::PENDING_INTRODUCED();
    }

    /**
     * Determine if a wrestler is bookable.
     *
     * @return bool
     */
    public function getIsBookableAttribute()
    {
        return $this->is_employed && !($this->is_retired || $this->is_injured || $this->is_suspended);
    }

    /**
     * Determine if a wrestler is hired.
     *
     * @return bool
     */
    public function getIsEmployedAttribute()
    {
        return $this->employments()->where('started_at', '<=', now())->whereNull('ended_at')->exists();
    }

    /**
     * Determine if a wrestler is retired.
     *
     * @return bool
     */
    public function getIsRetiredAttribute()
    {
        return $this->retirements()->whereNull('ended_at')->exists();
    }

    /**
     * Determine if a wrestler is suspended.
     *
     * @return bool
     */
    public function getIsSuspendedAttribute()
    {
        return $this->suspensions()->whereNull('ended_at')->exists();
    }

    /**
     * Determine if a wrestler is injured.
     *
     * @return bool
     */
    public function getIsInjuredAttribute()
    {
        return $this->injuries()->whereNull('ended_at')->exists();
    }


    /**
     * Return the wrestler's height in feet.
     *
     * @return string
     */
    public function getFeetAttribute()
    {
        return floor($this->height / 12);
    }

    /**
     * Return the wrestler's height in inches.
     *
     * @return string
     */
    public function getInchesAttribute()
    {
        return $this->height % 12;
    }

    /**
     * Scope a query to only include bookable wrestlers.
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
     * Scope a query to only include inactive wrestlers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePendingIntroduced($query)
    {
        return $query->whereHas('employments', function (Builder $query) {
            $query->whereNull('started_at')->orWhere('started_at', '>', now());
        });
    }

    /**
     * Scope a query to only include retired wrestlers.
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
     * Scope a query to only include suspended wrestlers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended($query)
    {
        return $query->whereHas('suspensions', function ($query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Scope a query to only include injured wrestlers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInjured($query)
    {
        return $query->whereHas('injuries', function ($query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Activate a wrestler.
     *
     * @return bool
     */
    public function activate()
    {
        return $this->employments()->latest()->first()->update(['started_at' => now()]);
    }

    /**
     * Retire a wrestler.
     *
     * @return \App\Models\Retirement
     */
    public function retire()
    {
        if ($this->is_suspended) {
            $this->reinstate();
        }

        if ($this->is_injured) {
            $this->recover();
        }

        $this->retirements()->create(['started_at' => now()]);
    }

    /**
     * Unretire a wrestler.
     *
     * @return bool
     */
    public function unretire()
    {
        return $this->retirement()->update(['ended_at' => now()]);
    }

    /**
     * Suspend a wrestler.
     *
     * @return \App\Models\Suspension
     */
    public function suspend()
    {
        $this->suspensions()->create(['started_at' => now()]);
    }

    /**
     * Reinstate a wrestler.
     *
     * @return bool
     */
    public function reinstate()
    {
        $this->suspension()->update(['ended_at' => now()]);
    }

    /**
     * Injure a wrestler.
     *
     * @return \App\Models\Injury
     */
    public function injure()
    {
        $this->injuries()->create(['started_at' => now()]);
    }

    /**
     * Recover a wrestler.
     *
     * @return bool
     */
    public function recover()
    {
        $this->injury()->update(['ended_at' => now()]);
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
