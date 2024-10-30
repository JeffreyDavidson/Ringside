<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\ManagerBuilder;
use App\Enums\ManagerStatus;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends Model implements CanBeAStableMember, Employable, Injurable, Retirable, Suspendable
{
    use Concerns\CanJoinStables;
    use Concerns\Manageables;
    use Concerns\OwnedByUser;
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
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
        'status' => ManagerStatus::Unemployed->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ManagerStatus::class,
        ];
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @return ManagerBuilder<Manager>
     */
    public function newEloquentBuilder($query): ManagerBuilder // @pest-ignore-type
    {
        return new ManagerBuilder($query);
    }

    /**
     * Get all the employments of the model.
     *
     * @return HasMany<ManagerEmployment>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(ManagerEmployment::class);
    }

    /**
     * @return HasMany<ManagerInjury, $this>
     */
    public function injuries(): HasMany
    {
        return $this->hasMany(ManagerInjury::class);
    }

    /**
     * @return HasOne<ManagerInjury, $this>
     */
    public function currentInjury(): HasOne
    {
        return $this->injuries()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<ManagerInjury, $this>
     */
    public function previousInjuries(): HasMany
    {
        return $this->injuries()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<ManagerInjury, $this>
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
     * @return HasMany<ManagerSuspension, $this>
     */
    public function suspensions(): HasMany
    {
        return $this->hasMany(ManagerSuspension::class);
    }

    /**
     * @return HasOne<ManagerSuspension, $this>
     */
    public function currentSuspension(): HasOne
    {
        return $this->suspensions()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<ManagerSuspension, $this>
     */
    public function previousSuspensions(): HasMany
    {
        return $this->suspensions()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<ManagerSuspension, $this>
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

    /**
     * @return HasMany<ManagerRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(ManagerRetirement::class);
    }

    /**
     * @return HasOne<ManagerRetirement, $this>
     */
    public function currentRetirement(): HasOne
    {
        return $this->retirements()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<ManagerRetirement, $this>
     */
    public function previousRetirements(): HasMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<ManagerRetirement, $this>
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
     * Determine if the manager is available to manager manageables.
     */
    public function isAvailable(): bool
    {
        return $this->status->label() === ManagerStatus::Available->label();
    }

    /**
     * Determine if the model can be retired.
     */
    public function canBeRetired(): bool
    {
        if ($this->isNotInEmployment()) {
            return false;
        }

        return true;
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
