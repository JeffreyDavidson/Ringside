<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TitleQueryBuilder;
use App\Enums\TitleStatus;
use App\Models\Concerns\Activations;
use App\Models\Concerns\Competable;
use App\Models\Concerns\Deactivations;
use App\Models\Concerns\HasRetirements;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Title extends Model implements Activatable, Deactivatable, Retirable
{
    use Activations;
    use Competable;
    use Deactivations;
    use HasFactory;
    use HasRetirements;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'status'];

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
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\Builders\TitleQueryBuilder<Title>
     */
    public function newEloquentBuilder($query): TitleQueryBuilder
    {
        return new TitleQueryBuilder($query);
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        return $this->isCurrentlyActivated() || $this->isDeactivated();
    }

    /**
     * Retrieve the championships of the title.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function championships()
    {
        return $this->hasMany(TitleChampionship::class)->oldest('won_at');
    }

    /**
     * Retrieve the current championship reign being held.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currentChampionship()
    {
        return $this->hasOne(TitleChampionship::class)->whereNull('lost_at')->latestOfMany();
    }

    /**
     * Determines if a title has a current champion.
     *
     * @return bool
     */
    public function isVacant()
    {
        return $this->currentChampionship?->champion === null;
    }

    /**
     * Determine if the title can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        return $this->isRetired();
    }
}
