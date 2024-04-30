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
use App\Observers\TagTeamObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends Model implements Bookable, CanBeAStableMember, Employable, Manageable, Retirable, Suspendable
{
    use Concerns\CanJoinStables;
    use Concerns\CanWinTitles;
    use Concerns\HasEmployments;
    use Concerns\HasManagers;
    use Concerns\HasMatches;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;
    use Concerns\HasWrestlers;
    use Concerns\OwnedByUser;
    use HasFactory;
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
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => TagTeamStatus::Unemployed->value,
    ];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @return TagTeamBuilder<TagTeam>
     */
    public function newEloquentBuilder($query): TagTeamBuilder // @pest-ignore-type
    {
        return new TagTeamBuilder($query);
    }

    /**
     * Check to see if the tag team is bookable.
     */
    public function isBookable(): bool
    {
        return $this->status->value === TagTeamStatus::Bookable->value;
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TagTeamStatus::class,
        ];
    }
}
