<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\ActivationStatus;
use App\Models\Contracts\Debutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @template TStatusChange of Model The status change model class (e.g., TitleStatusChange)
 * @template TModel of Model The parent model class that can have status changes (e.g., Title)
 *
 * @phpstan-require-implements Debutable<TStatusChange, TModel>
 *
 * @see Debutable
 */
trait HasStatusHistory
{
    /** @use HasEnumStatus<ActivationStatus> */
    use HasEnumStatus;

    use ResolvesRelatedModels;

    /** @return HasMany<TStatusChange, TModel> */
    public function statusChanges(): HasMany
    {
        /** @var HasMany<TStatusChange, TModel> $relation */
        $relation = $this->hasMany($this->resolveStatusChangeModelClass())
            ->orderBy('changed_at');

        return $relation;
    }

    /**
     * Get the earliest status change.
     *
     * @return HasOne<TStatusChange, TModel>
     */
    public function debutStatusChange(): HasOne
    {
        /** @var HasOne<TStatusChange, TModel> $relation */
        $relation = $this->hasOne($this->resolveStatusChangeModelClass())
            ->oldest('changed_at');

        return $relation;
    }

    /**
     * Get the most recent status change.
     *
     * @return HasOne<TStatusChange, TModel>
     */
    public function latestStatusChange(): HasOne
    {
        /** @var HasOne<TStatusChange, TModel> $relation */
        $relation = $this->hasOne($this->resolveStatusChangeModelClass())
            ->latest('changed_at');

        return $relation;
    }

    public function hasDebuted(): bool
    {
        return $this->statusChanges()->exists();
    }

    public function debutedAt(): ?Carbon
    {
        return $this->debutStatusChange?->changed_at;
    }

    /**
     * Check if the status changed to a specific value on a specific date.
     *
     * @param  ActivationStatus  $status  The status to check for
     * @param  Carbon  $date  The date to check against
     */
    public function statusChangedTo(ActivationStatus $status, Carbon $date): bool
    {
        return $this->statusChanges()
            ->where('status', $status)
            ->whereDate('changed_at', $date)
            ->exists();
    }

    /**
     * Get the formatted debut date.
     *
     * Returns 'TBD' if the entity hasn't debuted yet.
     */
    public function getFormattedDebutDate(): string
    {
        $debutDate = $this->debutedAt();

        return $debutDate?->format('Y-m-d') ?? 'TBD';
    }

    /**
     * @throws LogicException
     * @return class-string<TStatusChange>
     */
    protected function resolveStatusChangeModelClass(): string
    {
        return $this->resolveRelatedModelClass('StatusChange');
    }
}
