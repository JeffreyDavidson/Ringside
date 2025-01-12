<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\StableBuilder;
use App\Enums\StableStatus;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Stable extends Model implements Activatable, Retirable
{
    use Concerns\HasMembers;
    use Concerns\OwnedByUser;

    /** @use HasBuilder<StableBuilder<static>> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\StableFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The minimum number of members allowed on a tag team.
     */
    public const int MIN_MEMBERS_COUNT = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'status',
    ];

    protected static string $builder = StableBuilder::class;

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
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => StableStatus::Unactivated,
    ];

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
     * @return HasOne<StableActivation, $this>
     */
    public function futureActivation(): HasOne
    {
        return $this->activations()
            ->whereNull('ended_at')
            ->where('started_at', '>', now())
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

    /**
     * @return HasOne<StableActivation, $this>
     */
    public function firstActivation(): HasOne
    {
        return $this->activations()
            ->one()
            ->ofMany('started_at', 'min');
    }

    public function hasActivations(): bool
    {
        return $this->activations()->count() > 0;
    }

    public function isCurrentlyActivated(): bool
    {
        return $this->currentActivation()->exists();
    }

    public function hasFutureActivation(): bool
    {
        return $this->futureActivation()->exists();
    }

    public function isNotInActivation(): bool
    {
        return $this->isDeactivated() || $this->hasFutureActivation() || $this->isRetired();
    }

    public function isUnactivated(): bool
    {
        return $this->activations()->count() === 0;
    }

    public function isDeactivated(): bool
    {
        return $this->previousActivation()->exists()
            && $this->futureActivation()->doesntExist()
            && $this->currentActivation()->doesntExist()
            && $this->currentRetirement()->doesntExist();
    }

    public function activatedOn(Carbon $activationDate): bool
    {
        return $this->currentActivation ? $this->currentActivation->started_at->eq($activationDate) : false;
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
}
