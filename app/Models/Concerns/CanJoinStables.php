<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use LogicException;

/**
 * @template TPivotModel of Pivot
 * @template TModel of Model
 *
 * @phpstan-require-implements CanBeAStableMember<TPivotModel, TModel>
 */
trait CanJoinStables
{
    use HasBelongsToOne;

    /**
     * @return array{table: string, foreignKey: string, pivot: class-string<Pivot>}
     */
    private function stableMembershipRelationConfiguration(): array
    {
        return match (true) {
            $this instanceof Wrestler => [
                'table' => 'stables_wrestlers',
                'foreignKey' => 'wrestler_id',
                'pivot' => StableWrestler::class,
            ],
            $this instanceof TagTeam => [
                'table' => 'stables_tag_teams',
                'foreignKey' => 'tag_team_id',
                'pivot' => StableTagTeam::class,
            ],
            default => throw new LogicException('Unsupported stable member type: '.static::class),
        };
    }

    /**
     * @return BelongsToMany<Stable, static, TPivotModel>
     */
    public function stables(): BelongsToMany
    {
        $configuration = $this->stableMembershipRelationConfiguration();

        /** @var BelongsToMany<Stable, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(
            Stable::class,
            $configuration['table'],
            $configuration['foreignKey'],
            'stable_id'
        )
            ->using($configuration['pivot'])
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();

        return $relation;
    }

    public function currentStable(): BelongsToOne
    {
        $configuration = $this->stableMembershipRelationConfiguration();

        return $this->belongsToOne(
            Stable::class,
            $configuration['table'],
            $configuration['foreignKey'],
            'stable_id'
        )
            ->using($configuration['pivot'])
            ->wherePivotNull('left_at')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Stable, static, TPivotModel> */
    public function previousStables(): BelongsToMany
    {
        $configuration = $this->stableMembershipRelationConfiguration();

        /** @var BelongsToMany<Stable, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(
            Stable::class,
            $configuration['table'],
            $configuration['foreignKey'],
            'stable_id'
        )
            ->using($configuration['pivot'])
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNotNull('left_at');

        return $relation;
    }

    public function isNotCurrentlyInStable(Stable $stable): bool
    {
        $currentStable = $this->currentStable;

        if (! $currentStable) {
            return true;
        }

        /** @phpstan-ignore-next-line */
        return method_exists($currentStable, 'isNot') && $currentStable->isNot($stable);
    }
}
