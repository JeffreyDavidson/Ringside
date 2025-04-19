<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\StableBuilder;
use App\Enums\ActivationStatus;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property ActivationStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StableActivation> $activations
 * @property-read StableActivation|null $currentActivation
 * @property-read StableWrestler|StableTagTeam|StableManager|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Manager> $currentManagers
 * @property-read StableRetirement|null $currentRetirement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TagTeam> $currentTagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Wrestler> $currentWrestlers
 * @property-read StableActivation|null $firstActivation
 * @property-read StableActivation|null $futureActivation
 * @property-read TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Manager> $managers
 * @property-read StableActivation|null $previousActivation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StableActivation> $previousActivations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Manager> $previousManagers
 * @property-read StableRetirement|null $previousRetirement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StableRetirement> $previousRetirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TagTeam> $previousTagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Wrestler> $previousWrestlers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StableRetirement> $retirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TagTeam> $tagTeams
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Wrestler> $wrestlers
 *
 * @method static \Database\Factories\StableFactory factory($count = null, $state = [])
 * @method static \App\Builders\StableBuilder newModelQuery()
 * @method static \App\Builders\StableBuilder newQuery()
 * @method static \App\Builders\StableBuilder query()
 * @method static \App\Builders\StableBuilder unactivated()
 * @method static \App\Builders\StableBuilder active()
 * @method static \App\Builders\StableBuilder retired()
 * @method static \App\Builders\StableBuilder inactive()
 * @method static \App\Builders\StableBuilder withFutureActivation()
 * @method static \App\Builders\StableBuilder onlyTrashed()
 * @method static \App\Builders\StableBuilder withTrashed()
 * @method static \App\Builders\StableBuilder withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Stable extends Model implements Activatable, Retirable
{
    use Concerns\HasMembers;
    use Concerns\IsActivatable;
    use Concerns\IsRetirable;
    use Concerns\OwnedByUser;

    /** @use HasBuilder<StableBuilder> */
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
            'status' => ActivationStatus::class,
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => ActivationStatus::Unactivated->value,
    ];

    /**
     * @return HasMany<StableActivation, $this>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(StableActivation::class);
    }

    /**
     * @return HasMany<StableRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(StableRetirement::class);
    }

    /**
     * Retrieve the readable name of the model.
     */
    public function getNameLabel(): string
    {
        return $this->name;
    }
}
