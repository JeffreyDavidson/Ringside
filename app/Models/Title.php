<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TitleBuilder;
use App\Enums\TitleStatus;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Title extends Model implements Activatable, Retirable
{
    use Concerns\HasChampionships;

    /** @use HasBuilder<TitleBuilder<static>> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\TitleFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => TitleStatus::Unactivated->value,
    ];

    protected static string $builder = TitleBuilder::class;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TitleStatus::class,
        ];
    }

    /**
     * @return HasMany<TitleActivation>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(TitleActivation::class);
    }

    /**
     * @return HasOne<TitleActivation>
     */
    public function currentActivation(): HasOne
    {
        return $this->activations()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasOne<TitleActivation>
     */
    public function futureActivation(): HasOne
    {
        return $this->activations()
            ->whereNull('ended_at')
            ->where('started_at', '>', now())
            ->one();
    }

    /**
     * @return HasMany<TitleActivation>
     */
    public function previousActivations(): HasMany
    {
        return $this->activations()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<TitleActivation>
     */
    public function previousActivation(): HasOne
    {
        return $this->previousActivations()
            ->one()
            ->ofMany('ended_at', 'max');
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
        return $this->currentActivation?->started_at->eq($activationDate);
    }

    /**
     * @return HasMany<TitleRetirement>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(TitleRetirement::class);
    }

    /**
     * @return HasOne<TitleRetirement>
     */
    public function currentRetirement(): HasOne
    {
        return $this->retirements()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<TitleRetirement>
     */
    public function previousRetirements(): HasMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<TitleRetirement>
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
