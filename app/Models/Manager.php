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
use Illuminate\Support\Carbon;

/**
 * @property \App\Enums\EmploymentStatus $status
 * @property Stable $currentStable
 * @property ManagerEmployment $firstEmployment
 * @property int $id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $full_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \App\Models\ManagerEmployment|null $currentEmployment
 * @property-read \App\Models\ManagerInjury|null $currentInjury
 * @property-read \App\Models\ManagerRetirement|null $currentRetirement
 * @property-read \App\Models\ManagerSuspension|null $currentSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam> $currentTagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler> $currentWrestlers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManagerEmployment> $employments
 * @property-read \App\Models\ManagerEmployment|null $futureEmployment
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManagerInjury> $injuries
 * @property-read \App\Models\ManagerEmployment|null $previousEmployment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManagerEmployment> $previousEmployments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManagerInjury> $previousInjuries
 * @property-read \App\Models\ManagerInjury|null $previousInjury
 * @property-read \App\Models\ManagerRetirement|null $previousRetirement
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManagerRetirement> $previousRetirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stable> $previousStables
 * @property-read \App\Models\ManagerSuspension|null $previousSuspension
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManagerSuspension> $previousSuspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam> $previousTagTeams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler> $previousWrestlers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManagerRetirement> $retirements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stable> $stables
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManagerSuspension> $suspensions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam> $tagTeams
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler> $wrestlers
 *
 * @method static ManagerBuilder<static>|Manager available()
 * @method static \Database\Factories\ManagerFactory factory($count = null, $state = [])
 * @method static ManagerBuilder<static>|Manager futureEmployed()
 * @method static ManagerBuilder<static>|Manager injured()
 * @method static ManagerBuilder<static>|Manager newModelQuery()
 * @method static ManagerBuilder<static>|Manager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager onlyTrashed()
 * @method static ManagerBuilder<static>|Manager query()
 * @method static ManagerBuilder<static>|Manager released()
 * @method static ManagerBuilder<static>|Manager retired()
 * @method static ManagerBuilder<static>|Manager suspended()
 * @method static ManagerBuilder<static>|Manager unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Manager extends Model implements CanBeAStableMember, Employable, Injurable, Retirable, Suspendable
{
    use Concerns\CanJoinStables;
    use Concerns\IsEmployable;
    use Concerns\IsInjurable;
    use Concerns\IsRetirable;
    use Concerns\IsSuspendable;
    use Concerns\Manageables;
    use Concerns\OwnedByUser;
    use HasBelongsToOne;

    /** @use HasBuilder<ManagerBuilder<static>> */
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
        if ($this->isNotInEmployment()) {
            return false;
        }

        return true;
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
}
