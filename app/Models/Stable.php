<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\StableQueryBuilder;
use App\Enums\StableStatus;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stable extends Model implements Activatable, Retirable
{
    use Concerns\HasActivations;
    use Concerns\HasMembers;
    use Concerns\OwnedByUser;
    use HasFactory;
    use SoftDeletes;

    /**
     * The minium number of members allowed on a tag team.
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
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => StableStatus::class,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query): StableQueryBuilder
    {
        return new StableQueryBuilder($query);
    }

    /**
     * Get the retirements of the model.
     */
    public function retirements(): MorphMany
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the model.
     */
    public function currentRetirement(): MorphOne
    {
        return $this->morphOne(Retirement::class, 'retiree')
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->limit(1);
    }

    /**
     * Get the previous retirements of the model.
     */
    public function previousRetirements(): MorphMany
    {
        return $this->morphMany(Retirement::class, 'retiree')
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous retirement of the model.
     */
    public function previousRetirement(): MorphOne
    {
        return $this->morphOne(Retirement::class, 'retiree')
            ->latest('ended_at')
            ->limit(1);
    }

    /**
     * Check to see if the model is retired.
     */
    public function isRetired(): bool
    {
        return $this->currentRetirement()->exists();
    }

    /**
     * Check to see if the model has been activated.
     */
    public function hasRetirements(): bool
    {
        return $this->retirements()->count() > 0;
    }

    /**
     * Determine if the stable can be retired.
     */
    public function canBeRetired(): bool
    {
        return $this->isCurrentlyActivated() || $this->isDeactivated();
    }

    /**
     * Determine if the stable can be unretired.
     */
    public function canBeUnretired(): bool
    {
        return $this->isRetired();
    }
}
