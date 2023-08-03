<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\TagTeamBuilder;
use App\Enums\TagTeamStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Fidum\EloquentMorphToOne\HasMorphToOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends Model implements Bookable, CanBeAStableMember, Employable, Manageable, Retirable, Suspendable
{
    use Concerns\CanJoinStables;
    use Concerns\HasEmployments;
    use Concerns\HasManagers;
    use Concerns\HasMatches;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;
    use Concerns\HasWrestlers;
    use Concerns\OwnedByUser;
    use HasFactory;
    use HasMorphToOne;
    use SoftDeletes;

    /**
     * The number of the wrestlers allowed on a tag team.
     */
    public const NUMBER_OF_WRESTLERS_ON_TEAM = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'signature_move',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => TagTeamStatus::class,
    ];

    public static function query(): TagTeamBuilder
    {
        return parent::query();
    }

    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query): TagTeamBuilder
    {
        return new TagTeamBuilder($query);
    }

    /**
     * Check to see if the tag team is bookable.
     */
    public function isBookable(): bool
    {
        if ($this->isNotInEmployment() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }

    /**
     * Check to see if the tag team is unbookable.
     */
    public function isUnbookable(): bool
    {
        return ! $this->currentWrestlers->every(fn (Wrestler $wrestler) => $wrestler->isBookable());
    }

    public function getIdentifier(): string
    {
        return $this->name;
    }
}
