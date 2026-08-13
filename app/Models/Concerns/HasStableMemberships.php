<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\CanBeAStableMember;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @template TPivotModel of Pivot
 * @template TModel of Model
 *
 * @phpstan-require-implements CanBeAStableMember<TPivotModel, TModel>
 */
trait HasStableMemberships
{
    abstract protected function stableMembershipTable(): string;

    abstract protected function stableMembershipForeignKey(): string;

    /** @return class-string<TPivotModel> */
    abstract protected function stableMembershipPivotModel(): string;

    /** @return array{table: string, foreignKey: string, pivot: class-string<TPivotModel>} */
    private function stableMembershipRelationConfiguration(): array
    {
        return [
            'table' => $this->stableMembershipTable(),
            'foreignKey' => $this->stableMembershipForeignKey(),
            'pivot' => $this->stableMembershipPivotModel(),
        ];
    }

    /**
     * @return BelongsToMany<Stable, $this, TPivotModel>
     */
    public function stables(): BelongsToMany
    {
        $configuration = $this->stableMembershipRelationConfiguration();

        /** @var BelongsToMany<Stable, $this, TPivotModel> $relation */
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

    public function currentStable(): HasOneThrough
    {
        $configuration = $this->stableMembershipRelationConfiguration();

        return $this->hasOneThrough(
            Stable::class,
            $configuration['pivot'],
            $configuration['foreignKey'],
            'id',
            'id',
            'stable_id'
        )
            ->whereNull("{$configuration['table']}.left_at");
    }

    /** @return BelongsToMany<Stable, $this, TPivotModel> */
    public function previousStables(): BelongsToMany
    {
        $configuration = $this->stableMembershipRelationConfiguration();

        /** @var BelongsToMany<Stable, $this, TPivotModel> $relation */
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
}
