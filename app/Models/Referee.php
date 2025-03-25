<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\RefereeBuilder;
use App\Enums\EmploymentStatus;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property \App\Enums\EmploymentStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\RefereeEmployment|null $currentEmployment
 * @property-read \App\Models\RefereeInjury|null $currentInjury
 * @property-read \App\Models\RefereeRetirement|null $currentRetirement
 * @property-read \App\Models\RefereeSuspension|null $currentSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RefereeEmployment> $employments
 * @property-read \App\Models\RefereeEmployment|null $firstEmployment
 * @property-read \App\Models\RefereeEmployment|null $futureEmployment
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RefereeInjury> $injuries
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMatch> $matches
 * @property-read \App\Models\RefereeEmployment|null $previousEmployment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RefereeEmployment> $previousEmployments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RefereeInjury> $previousInjuries
 * @property-read \App\Models\RefereeInjury|null $previousInjury
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventMatch> $previousMatches
 * @property-read \App\Models\RefereeRetirement|null $previousRetirement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RefereeRetirement> $previousRetirements
 * @property-read \App\Models\RefereeSuspension|null $previousSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RefereeSuspension> $previousSuspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RefereeRetirement> $retirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RefereeSuspension> $suspensions
 *
 * @method static \Database\Factories\RefereeFactory factory($count = null, $state = [])
 * @method static \App\Builders\RefereeBuilder newModelQuery()
 * @method static \App\Builders\RefereeBuilder newQuery()
 * @method static \App\Builders\RefereeBuilder query()
 * @method static \App\Builders\RefereeBuilder bookable()
 * @method static \App\Builders\RefereeBuilder injured()
 * @method static \App\Builders\RefereeBuilder unemployed()
 * @method static \App\Builders\RefereeBuilder retired()
 * @method static \App\Builders\RefereeBuilder released()
 * @method static \App\Builders\RefereeBuilder suspended()
 * @method static \App\Builders\RefereeBuilder futureEmployed()
 * @method static \App\Builders\RefereeBuilder onlyTrashed()
 * @method static \App\Builders\RefereeBuilder withTrashed()
 * @method static \App\Builders\RefereeBuilder withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Referee extends Model implements Employable, Injurable, Retirable, Suspendable
{
    use Concerns\IsEmployable;
    use Concerns\IsInjurable;
    use Concerns\IsRetirable;
    use Concerns\IsSuspendable;

    /** @use HasBuilder<RefereeBuilder> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\RefereeFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
        'status' => EmploymentStatus::Unemployed->value,
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
            'status' => EmploymentStatus::class,
        ];
    }

    /**
     * Get all the employments of the model.
     *
     * @return HasMany<RefereeEmployment, $this>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(RefereeEmployment::class);
    }

    /**
     * @return HasMany<RefereeInjury, $this>
     */
    public function injuries(): HasMany
    {
        return $this->hasMany(RefereeInjury::class);
    }

    /**
     * @return HasMany<RefereeSuspension, $this>
     */
    public function suspensions(): HasMany
    {
        return $this->hasMany(RefereeSuspension::class);
    }

    /**
     * @return HasMany<RefereeRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(RefereeRetirement::class);
    }

    /**
     * Retrieve the event matches participated by the model.
     *
     * @return BelongsToMany<EventMatch, $this>
     */
    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(EventMatch::class);
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
     * Retrieve the readable name of the model.
     */
    public function getNameLabel(): string
    {
        return $this->full_name;
    }
}
