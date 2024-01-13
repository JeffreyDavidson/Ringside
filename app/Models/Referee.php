<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\RefereeBuilder;
use App\Enums\RefereeStatus;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referee extends Model implements Employable, Injurable, Retirable, Suspendable
{
    use Concerns\HasEmployments;
    use Concerns\HasInjuries;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => RefereeStatus::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => RefereeStatus::Unemployed->value,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @return RefereeBuilder<Referee>
     */
    public function newEloquentBuilder($query): RefereeBuilder // @pest-ignore-type
    {
        return new RefereeBuilder($query);
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

    public function getIdentifier(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Retrieve the event matches participated by the model.
     *
     * @return BelongsToMany<EventMatch>
     */
    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(EventMatch::class);
    }

    /**
     * Retrieve the event matches participated by the model.
     *
     * @return BelongsToMany<EventMatch>
     */
    public function previousMatches(): BelongsToMany
    {
        return $this->matches()
            ->join('events', 'event_matches.event_id', '=', 'events.id')
            ->where('events.date', '<', today());
    }

    /**
     * Get the referee's full name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }
}
