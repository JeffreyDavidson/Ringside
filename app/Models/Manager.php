<?php

declare(strict_types=1);

namespace App\Models;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Builders\ManagerBuilder;
use App\Enums\EmploymentStatus;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property EmploymentStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read ManagerEmployment|null $currentEmployment
 * @property-read ManagerInjury|null $currentInjury
 * @property-read ManagerRetirement|null $currentRetirement
 * @property-read ManagerSuspension|null $currentSuspension
 * @property-read Stable|null $currentStable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TagTeam> $currentTagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Wrestler> $currentWrestlers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ManagerEmployment> $employments
 * @property-read ManagerEmployment|null $firstEmployment
 * @property-read ManagerEmployment|null $futureEmployment
 * @property-read TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ManagerInjury> $injuries
 * @property-read ManagerEmployment|null $previousEmployment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ManagerEmployment> $previousEmployments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ManagerInjury> $previousInjuries
 * @property-read ManagerInjury|null $previousInjury
 * @property-read ManagerRetirement|null $previousRetirement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ManagerRetirement> $previousRetirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stable> $previousStables
 * @property-read ManagerSuspension|null $previousSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ManagerSuspension> $previousSuspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TagTeam> $previousTagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Wrestler> $previousWrestlers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ManagerRetirement> $retirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stable> $stables
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ManagerSuspension> $suspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TagTeam> $tagTeams
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Wrestler> $wrestlers
 *
 * @method static \Database\Factories\ManagerFactory factory($count = null, $state = [])
 * @method static \App\Builders\ManagerBuilder newModelQuery()
 * @method static \App\Builders\ManagerBuilder newQuery()
 * @method static \App\Builders\ManagerBuilder query()
 * @method static \App\Builders\ManagerBuilder available()
 * @method static \App\Builders\ManagerBuilder futureEmployed()
 * @method static \App\Builders\ManagerBuilder injured()
 * @method static \App\Builders\ManagerBuilder released()
 * @method static \App\Builders\ManagerBuilder retired()
 * @method static \App\Builders\ManagerBuilder suspended()
 * @method static \App\Builders\ManagerBuilder unemployed()
 * @method static \App\Builders\ManagerBuilder onlyTrashed()
 * @method static \App\Builders\ManagerBuilder withTrashed()
 * @method static \App\Builders\ManagerBuilder withoutTrashed()
 *
 * @mixin \Eloquent
 */
final class Manager extends Model implements CanBeAStableMember, Employable, Injurable, Retirable, Suspendable
{
    use Concerns\CanJoinStables;
    use Concerns\IsEmployable;
    use Concerns\IsInjurable;
    use Concerns\IsRetirable;
    use Concerns\IsSuspendable;
    use Concerns\Manageables;
    use Concerns\OwnedByUser;
    use HasBelongsToOne;

    /** @use HasBuilder<ManagerBuilder> */
    use HasBuilder;

    /** @use HasFactory<\Database\Factories\ManagerFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
        'status' => EmploymentStatus::Unemployed->value,
    ];

    protected static string $builder = ManagerBuilder::class;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmploymentStatus::class,
        ];
    }

    /**
     * Get all the employments of the model.
     *
     * @return HasMany<ManagerEmployment, $this>
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
     * @return HasMany<ManagerSuspension, $this>
     */
    public function suspensions(): HasMany
    {
        return $this->hasMany(ManagerSuspension::class);
    }

    /**
     * @return HasMany<ManagerRetirement, $this>
     */
    public function retirements(): HasMany
    {
        return $this->hasMany(ManagerRetirement::class);
    }

    /**
     * Determine if the manager is available to manager manageables.
     */
    public function isAvailable(): bool
    {
        return $this->status->label() === EmploymentStatus::Available->label();
    }

    /**
     * Determine if the model can be retired.
     */
    public function canBeRetired(): bool
    {
        return !$this->isNotInEmployment();
    }

    /**
     * Get the stables the model has been belonged to.
     *
     * @return BelongsToMany<Stable, $this>
     */
    public function stables(): BelongsToMany
    {
        return $this->belongsToMany(Stable::class, 'stables_managers')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Get the current stable the member belongs to.
     */
    public function currentStable(): BelongsToOne
    {
        return $this->belongsToOne(Stable::class, 'stables_managers')
            ->wherePivotNull('left_at')
            ->withTimestamps();
    }

    /**
     * Retrieve the readable name of the model.
     */
    public function getNameLabel(): string
    {
        return $this->full_name;
    }
}
