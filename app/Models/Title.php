<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TitleQueryBuilder;
use App\Enums\TitleStatus;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Title extends Model implements Activatable, Deactivatable, Retirable
{
    use Concerns\Activations;
    use Concerns\Competable;
    use Concerns\Deactivations;
    use Concerns\HasRetirements;
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
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => TitleStatus::class,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query): TitleQueryBuilder
    {
        return new TitleQueryBuilder($query);
    }

    /**
     * Retrieve the championships for a title.
     */
    public function championships(): HasMany
    {
        return $this->hasMany(TitleChampionship::class)->oldest('won_at');
    }

    /**
     * Retrieve the curren championship for a title.
     */
    public function currentChampionship(): HasOne
    {
        return $this->hasOne(TitleChampionship::class)->whereNull('lost_at')->latestOfMany();
    }

    /**
     * Determine if the title is vacant.
     */
    public function isVacant(): bool
    {
        return $this->currentChampionship?->champion === null;
    }

    /**
     * Determine if a title can be retired.
     */
    public function canBeRetired(): bool
    {
        return $this->isCurrentlyActivated() || $this->isDeactivated();
    }

    /**
     * Determine if a title can be unretired.
     */
    public function canBeUnretired(): bool
    {
        return $this->isRetired();
    }
}
