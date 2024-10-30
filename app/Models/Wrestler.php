<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\WrestlerBuilder;
use App\Casts\HeightCast;
use App\Enums\WrestlerStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Contracts\TagTeamMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wrestler extends Model implements Bookable, CanBeAStableMember, Employable, Injurable, Manageable, Retirable, Suspendable, TagTeamMember
{
    use Concerns\CanJoinStables;
    use Concerns\CanJoinTagTeams;
    use Concerns\CanWinTitles;
    use Concerns\HasManagers;
    use Concerns\HasMatches;
    use Concerns\OwnedByUser;

    /** @use HasFactory<\Database\Factories\WrestlerFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'height',
        'weight',
        'hometown',
        'signature_move',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => WrestlerStatus::Unemployed->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'height' => HeightCast::class,
            'status' => WrestlerStatus::class,
        ];
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @return WrestlerBuilder<Wrestler>
     */
    public function newEloquentBuilder($query): WrestlerBuilder // @pest-ignore-type
    {
        return new WrestlerBuilder($query);
    }

    /**
     * Get all the employments of the model.
     *
     * @return HasMany<WrestlerEmployment>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(WrestlerEmployment::class);
    }

    /**
     * @return HasOne<WrestlerEmployment, $this>
     */
    public function currentEmployment(): HasOne
    {
        return $this->employments()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasOne<ManagerEmployment, $this>
     */
    public function futureEmployment(): HasOne
    {
        return $this->employments()
            ->whereNull('ended_at')
            ->where('started_at', '>', now())
            ->one();
    }

    /**
     * @return HasMany<WrestlerEmployment, $this>
     */
    public function previousEmployments(): HasMany
    {
        return $this->employments()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<WrestlerEmployment, $this>
     */
    public function previousEmployment(): HasOne
    {
        return $this->previousEmployments()
            ->latestOfMany()
            ->one();
    }

    public function latestCurrentEmployment(): HasOne
    {
        return $this->employments()->one()->ofMany([
            'started_at' => 'max',
        ], function (Builder $query) {
            $query->whereNull('ended_at')
                ->orWhere('ended_at', '>=', now());
        });
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

    /**
     * @return HasMany<WrestlerRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(WrestlerRetirement::class);
    }

    /**
     * @return HasOne<WrestlerRetirement, $this>
     */
    public function currentRetirement(): HasOne
    {
        return $this->retirements()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<WrestlerRetirement, $this>
     */
    public function previousRetirements(): HasMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<WrestlerRetirement, $this>
     */
    public function previousRetirement(): HasOne
    {
        return $this->previousRetirements()
            ->latestOfMany()
            ->one();
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
     * @return HasMany<WrestlerInjury, $this>
     */
    public function injuries(): HasMany
    {
        return $this->hasMany(WrestlerInjury::class);
    }

    /**
     * @return HasOne<WrestlerInjury, $this>
     */
    public function currentInjury(): HasOne
    {
        return $this->injuries()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<WrestlerInjury, $this>
     */
    public function previousInjuries(): HasMany
    {
        return $this->injuries()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<WrestlerInjury, $this>
     */
    public function previousInjury(): HasOne
    {
        return $this->previousInjuries()
            ->latestOfMany()
            ->one();
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
     * @return HasMany<WrestlerSuspension, $this>
     */
    public function suspensions(): HasMany
    {
        return $this->hasMany(WrestlerSuspension::class);
    }

    /**
     * @return HasOne<WrestlerSuspension, $this>
     */
    public function currentSuspension(): HasOne
    {
        return $this->suspensions()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<WrestlerSuspension, $this>
     */
    public function previousSuspensions(): HasMany
    {
        return $this->suspensions()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<WrestlerSuspension, $this>
     */
    public function previousSuspension(): HasOne
    {
        return $this->suspensions()
            ->latestOfMany('ended_at')
            ->one();
    }

    public function isSuspended(): bool
    {
        return $this->currentSuspension()->exists();
    }

    public function hasSuspensions(): bool
    {
        return $this->suspensions()->count() > 0;
    }
}
