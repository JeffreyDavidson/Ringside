<?php

declare(strict_types=1);

namespace App\Models\Managers;

use Ankurk91\Eloquent\HasBelongsToOne;
use App\Builders\Roster\ManagerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\DefinesManagedAliases;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Concerns\ValidatesEmployment;
use App\Models\Concerns\ValidatesInjury;
use App\Models\Concerns\ValidatesRestoration;
use App\Models\Concerns\ValidatesRetirement;
use App\Models\Concerns\ValidatesSuspension;
use App\Models\Contracts\Employable;
use App\Models\Contracts\HasDisplayName;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Managers\ManagerFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Tests\Unit\Models\Managers\ManagerTest;

/**
 * @implements Employable<ManagerEmployment, static>
 * @implements Injurable<ManagerInjury, static>
 * @implements Retirable<ManagerRetirement, static>
 * @implements Suspendable<ManagerSuspension, static>
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property-read string $full_name
 * @property EmploymentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ManagerEmployment|null $currentEmployment
 * @property-read ManagerEmployment|null $firstEmployment
 * @property-read ManagerEmployment|null $futureEmployment
 * @property-read ManagerEmployment|null $previousEmployment
 * @property-read Collection<int, ManagerEmployment> $employments
 * @property-read Collection<int, ManagerEmployment> $previousEmployments
 * @property-read ManagerInjury|null $currentInjury
 * @property-read ManagerInjury|null $previousInjury
 * @property-read Collection<int, ManagerInjury> $injuries
 * @property-read Collection<int, ManagerInjury> $previousInjuries
 * @property-read ManagerRetirement|null $currentRetirement
 * @property-read ManagerRetirement|null $previousRetirement
 * @property-read Collection<int, ManagerRetirement> $retirements
 * @property-read Collection<int, ManagerRetirement> $previousRetirements
 * @property-read ManagerSuspension|null $currentSuspension
 * @property-read ManagerSuspension|null $previousSuspension
 * @property-read Collection<int, ManagerSuspension> $suspensions
 * @property-read Collection<int, ManagerSuspension> $previousSuspensions
 * @property-read Collection<int, Wrestler> $wrestlers
 * @property-read Collection<int, Wrestler> $currentWrestlers
 * @property-read Collection<int, Wrestler> $previousWrestlers
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, TagTeam> $currentTagTeams
 * @property-read Collection<int, TagTeam> $previousTagTeams
 * @property-read mixed $display_name
 *
 * @method static ManagerBuilder<static>|Manager available()
 * @method static ManagerBuilder<static>|Manager availableOn(\Carbon\Carbon $date)
 * @method static ManagerBuilder<static>|Manager employed()
 * @method static \Database\Factories\Managers\ManagerFactory factory($count = null, $state = [])
 * @method static ManagerBuilder<static>|Manager futureEmployed()
 * @method static ManagerBuilder<static>|Manager injured()
 * @method static ManagerBuilder<static>|Manager newModelQuery()
 * @method static ManagerBuilder<static>|Manager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager onlyTrashed()
 * @method static ManagerBuilder<static>|Manager query()
 * @method static ManagerBuilder<static>|Manager released()
 * @method static ManagerBuilder<static>|Manager retired()
 * @method static ManagerBuilder<static>|Manager suspended()
 * @method static ManagerBuilder<static>|Manager unavailable()
 * @method static ManagerBuilder<static>|Manager unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager withoutTrashed()
 *
 * @mixin \Eloquent
 *
 * @see ManagerTest
 */
#[UseFactory(ManagerFactory::class)]
#[UseEloquentBuilder(ManagerBuilder::class)]
class Manager extends Model implements Employable, HasDisplayName, Injurable, Retirable, Suspendable
{
    use DefinesManagedAliases;
    use HasBelongsToOne;
    use HasFactory;

    /** @use IsEmployable<ManagerEmployment, static> */
    use IsEmployable;

    /** @use IsInjurable<ManagerInjury, static> */
    use IsInjurable;

    /** @use IsRetirable<ManagerRetirement, static> */
    use IsRetirable;

    /** @use IsSuspendable<ManagerSuspension, static> */
    use IsSuspendable;

    use ProvidesDisplayName;
    use SoftDeletes;
    use ValidatesEmployment;
    use ValidatesInjury;
    use ValidatesRestoration;
    use ValidatesRetirement;
    use ValidatesSuspension {
        ValidatesSuspension::isUnemployed insteadof ValidatesInjury;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
}
