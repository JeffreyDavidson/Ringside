<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\StableBuilder;
use App\Enums\StableStatus;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stable extends Model implements Activatable, Retirable
{
    use Concerns\HasMembers;
    use Concerns\OwnedByUser;

    /** @use HasFactory<\Database\Factories\StableFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The minimum number of members allowed on a tag team.
     */
    public const MIN_MEMBERS_COUNT = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StableStatus::class,
        ];
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @return StableBuilder<Stable>
     */
    public function newEloquentBuilder($query): StableBuilder // @pest-ignore-type
    {
        return new StableBuilder($query);
    }

    /**
     * @return HasMany<StableActivation, $this>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(StableActivation::class);
    }

    /**
     * @return HasOne<StableActivation, $this>
     */
    public function currentActivation(): HasOne
    {
        return $this->activations()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<StableActivation, $this>
     */
    public function previousActivations(): HasMany
    {
        return $this->activations()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<StableActivation, $this>
     */
    public function previousActivation(): HasOne
    {
        return $this->previousActivations()
            ->latest('ended_at')
            ->one();
    }

    public function isActivated(): bool
    {
        return $this->currentActivation()->exists();
    }

    public function hasActivations(): bool
    {
        return $this->activations()->count() > 0;
    }

    /**
     * @return HasMany<StableRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(StableRetirement::class);
    }

    /**
     * @return HasOne<StableRetirement, $this>
     */
    public function currentRetirement(): HasOne
    {
        return $this->retirements()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<StableRetirement, $this>
     */
    public function previousRetirements(): HasMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<StableRetirement, $this>
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
}
