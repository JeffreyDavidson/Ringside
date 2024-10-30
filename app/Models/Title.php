<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TitleBuilder;
use App\Enums\TitleStatus;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Title extends Model implements Retirable
{
    use Concerns\HasChampionships;

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
     * Create a new Eloquent query builder for the model.
     *
     * @return TitleBuilder<Title>
     */
    public function newEloquentBuilder($query): TitleBuilder // @pest-ignore-type
    {
        return new TitleBuilder($query);
    }

    /**
     * Get all the activations of the model.
     *
     * @return HasMany<TitleActivation>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(TitleActivation::class);
    }

    /**
     * @return HasMany<TitleRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(TitleRetirement::class);
    }

    /**
     * @return HasOne<TitleRetirement, $this>
     */
    public function currentRetirement(): HasOne
    {
        return $this->retirements()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * @return HasMany<TitleRetirement, $this>
     */
    public function previousRetirements(): HasMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    /**
     * @return HasOne<TitleRetirement, $this>
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
