<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\RefereeBuilder;
use App\Enums\RefereeStatus;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Referee extends Model implements Employable, Injurable, Retirable, Suspendable
{
    /** @use HasBuilder<RefereeBuilder<static>> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\RefereeFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => RefereeStatus::Unemployed->value,
    ];

    protected static string $builder = RefereeBuilder::class;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RefereeStatus::class,
        ];
    }

    /**
     * Get all the employments of the model.
     *
     * @return HasMany<RefereeEmployment>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(RefereeEmployment::class);
    }

    /**
     * @return HasOne<RefereeEmployment>
     */
    public function currentEmployment(): HasOne
    {
        return $this->employments()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasOne<RefereeEmployment>
     */
    public function futureEmployment(): HasOne
    {
        return $this->employments()
            ->whereNull('ended_at')
            ->where('started_at', '>', now())
            ->one();
    }

    /**
     * @return HasMany<RefereeEmployment>
     */
    public function previousEmployments(): HasMany
    {
        return $this->employments()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<RefereeEmployment>
     */
    public function previousEmployment(): HasOne
    {
        return $this->previousEmployments()
            ->one()
            ->ofMany('ended_at', 'max');
    }

    public function hasEmployments(): bool
    {
        return $this->employments()->count() > 0;
    }

    public function isCurrentlyEmployed(): bool
    {
        return $this->currentEmployment()->exists();
    }

    public function hasFutureEmployment(): bool
    {
        return $this->futureEmployment()->exists();
    }

    public function isNotInEmployment(): bool
    {
        return $this->isUnemployed() || $this->isReleased() || $this->isRetired();
    }

    public function isUnemployed(): bool
    {
        return $this->employments()->count() === 0;
    }

    public function isReleased(): bool
    {
        return $this->previousEmployment()->exists()
            && $this->futureEmployment()->doesntExist()
            && $this->currentEmployment()->doesntExist()
            && $this->currentRetirement()->doesntExist();
    }

    public function employedOn(Carbon $employmentDate): bool
    {
        return $this->currentEmployment ? $this->currentEmployment->started_at->eq($employmentDate) : false;
    }

    public function employedBefore(Carbon $employmentDate): bool
    {
        return $this->currentEmployment ? $this->currentEmployment->started_at->lte($employmentDate) : false;
    }

    /**
     * Check to see if the model is bookable.
     */
    public function isBookable(): bool
    {
        if ($this->isNotInEmployment() || $this->isSuspended() || $this->isInjured() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve the event matches participated by the model.
     *
     * @return BelongsToMany<EventMatch>
     */
    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(EventMatch::class);
    }

    /**
     * Retrieve the event matches participated by the model.
     *
     * @return BelongsToMany<EventMatch>
     */
    public function previousMatches(): BelongsToMany
    {
        return $this->matches()
            ->join('events', 'event_matches.event_id', '=', 'events.id')
            ->where('events.date', '<', today());
    }

    /**
     * @return HasMany<RefereeInjury>
     */
    public function injuries(): HasMany
    {
        return $this->hasMany(RefereeInjury::class);
    }

    /**
     * @return HasOne<RefereeInjury>
     */
    public function currentInjury(): HasOne
    {
        return $this->injuries()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<RefereeInjury>
     */
    public function previousInjuries(): HasMany
    {
        return $this->injuries()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<RefereeInjury>
     */
    public function previousInjury(): HasOne
    {
        return $this->previousInjuries()
            ->one()
            ->ofMany('ended_at', 'max');
    }

    public function isInjured(): bool
    {
        return $this->currentInjury()->exists();
    }

    public function hasInjuries(): bool
    {
        return $this->injuries()->count() > 0;
    }

    /**
     * @return HasMany<RefereeSuspension>
     */
    public function suspensions(): HasMany
    {
        return $this->hasMany(RefereeSuspension::class);
    }

    /**
     * @return HasOne<RefereeSuspension>
     */
    public function currentSuspension(): HasOne
    {
        return $this->suspensions()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<RefereeSuspension>
     */
    public function previousSuspensions(): HasMany
    {
        return $this->suspensions()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<RefereeSuspension>
     */
    public function previousSuspension(): HasOne
    {
        return $this->suspensions()
            ->one()
            ->ofMany('ended_at', 'max');
    }

    public function isSuspended(): bool
    {
        return $this->currentSuspension()->exists();
    }

    public function hasSuspensions(): bool
    {
        return $this->suspensions()->count() > 0;
    }

    /**
     * @return HasMany<RefereeRetirement>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(RefereeRetirement::class);
    }

    /**
     * @return HasOne<RefereeRetirement>
     */
    public function currentRetirement(): HasOne
    {
        return $this->retirements()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<RefereeRetirement>
     */
    public function previousRetirements(): HasMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<RefereeRetirement>
     */
    public function previousRetirement(): HasOne
    {
        return $this->previousRetirements()
            ->one()
            ->ofMany('ended_at', 'max');
    }

    public function isRetired(): bool
    {
        return $this->currentRetirement()->exists();
    }

    public function hasRetirements(): bool
    {
        return $this->retirements()->count() > 0;
    }

    /**
     * Get the manager's full name.
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }
}
