<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\ActivationStatus;
use App\Models\Contracts\Debutable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Adds status history behavior to a model.
 *
 * This trait provides a complete status change tracking system for Eloquent models, including
 * methods to manage status changes over time, debut tracking, and current status determination.
 * Perfect for entities like titles and stables that debut and have their status tracked historically.
 *
 * @template TStatusChange of Model The status change model class (e.g., TitleStatusChange)
 * @template TModel of Model The parent model class that can have status changes (e.g., Title)
 *
 * @phpstan-require-implements Debutable<TStatusChange, TModel>
 *
 * @see Debutable
 *
 * @example
 * ```php
 * // In your model:
 * class Title extends Model implements Debutable
 * {
 *     use HasStatusHistory;
 * }
 *
 * // Usage:
 * $title = Title::find(1);
 * $title->hasDebuted();              // Check if ever debuted
 * $title->debutedAt();               // Get debut date
 * $title->statusChanges;             // Get all status changes
 * $title->latestStatusChange;        // Get current status change
 * ```
 */
trait HasStatusHistory
{
    /** @use HasEnumStatus<ActivationStatus> */
    use HasEnumStatus;

    use ResolvesRelatedModels;

    /**
     * Get all status changes for the model.
     *
     * This method returns a HasMany relationship that includes all status change records
     * for the model, ordered chronologically by when they occurred.
     *
     * @return HasMany<TStatusChange, TModel> The relationship instance
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $allChanges = $title->statusChanges;
     * $changeCount = $title->statusChanges()->count();
     * $recentChanges = $title->statusChanges()->latest('changed_at')->take(5)->get();
     * ```
     */
    public function statusChanges(): HasMany
    {
        /** @var HasMany<TStatusChange, TModel> $relation */
        $relation = $this->hasMany($this->resolveStatusChangeModelClass())
            ->orderBy('changed_at');

        return $relation;
    }

    /**
     * Get the debut (first) status change record.
     *
     * Returns a HasOne relationship for the first status change record,
     * which represents when the entity debuted (was first introduced).
     *
     * @return HasOne<TStatusChange, TModel> The relationship instance
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $debutRecord = $title->debutStatusChange;
     *
     * if ($title->debutStatusChange()->exists()) {
     *     echo "Title debuted on: " . $title->debutStatusChange->changed_at;
     * }
     * ```
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
     * Returns a HasOne relationship for the most recent status change record,
     * which determines the current status of the entity.
     *
     * @return HasOne<TStatusChange, TModel> The relationship instance
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $latestChange = $title->latestStatusChange;
     * $currentStatus = $title->latestStatusChange->status;
     * ```
     */
    public function latestStatusChange(): HasOne
    {
        /** @var HasOne<TStatusChange, TModel> $relation */
        $relation = $this->hasOne($this->resolveStatusChangeModelClass())
            ->latest('changed_at');

        return $relation;
    }

    /**
     * Determine if the model has debuted (has any status changes).
     *
     * Checks if there are any status change records associated with this model,
     * indicating the entity has been introduced/debuted.
     *
     * @return bool True if the model has debuted, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->hasDebuted()) {
     *     echo "This title has been introduced";
     * }
     * ```
     */
    public function hasDebuted(): bool
    {
        return $this->statusChanges()->exists();
    }

    /**
     * Get the debut date of the entity.
     *
     * Returns the date when the entity first debuted, or null if it hasn't debuted yet.
     *
     * @return Carbon|null The debut date, or null if not debuted
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $debutDate = $title->debutedAt();
     *
     * if ($debutDate) {
     *     echo "Title debuted on: " . $debutDate->format('Y-m-d');
     * }
     * ```
     */
    public function debutedAt(): ?Carbon
    {
        return $this->debutStatusChange?->changed_at;
    }

    /**
     * Determine if the model is currently active.
     *
     * Checks the latest status change to determine if the entity is currently active.
     * If there are no status changes, falls back to the model's status attribute.
     *
     * @return bool True if the model is currently active, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->isCurrentlyActive()) {
     *     echo "This title is currently active";
     * }
     * ```
     */
    public function isCurrentlyActive(): bool
    {
        $latestStatusChange = $this->latestStatusChange;

        if ($latestStatusChange) {
            return $latestStatusChange->status === ActivationStatus::Active;
        }

        // Fall back to the model's current status if no history exists
        return $this->hasStatus(ActivationStatus::Active);
    }

    /**
     * Check if the model is not currently active.
     *
     * Considers the model not active if its latest status is inactive, unactivated,
     * or if it's retired (if the model supports retirement).
     *
     * @return bool True if the model is not currently active, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->isNotCurrentlyActive()) {
     *     echo "Title is not currently available";
     * }
     * ```
     */
    public function isNotCurrentlyActive(): bool
    {
        if (! $this->isCurrentlyActive()) {
            return true;
        }

        // Check if the model is retired (assuming it implements IsRetirable)
        return $this instanceof Retirable && $this->isRetired();
    }

    /**
     * Check if the model's status is Unactivated.
     *
     * @return bool True if the status is Unactivated, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->isUnactivated()) {
     *     echo "This title has never been activated";
     * }
     * ```
     */
    public function isUnactivated(): bool
    {
        return $this->hasStatus(ActivationStatus::Unactivated);
    }

    /**
     * Check if the model's status is Inactive.
     *
     * @return bool True if the status is Inactive, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->isInactive()) {
     *     echo "This title is currently inactive";
     * }
     * ```
     */
    public function isInactive(): bool
    {
        return $this->hasStatus(ActivationStatus::Inactive);
    }

    /**
     * Check if the status changed to a specific value on a specific date.
     *
     * @param  ActivationStatus  $status  The status to check for
     * @param  Carbon  $date  The date to check against
     * @return bool True if status changed to the specified value on the date, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $targetDate = Carbon::parse('2024-01-15');
     *
     * if ($title->statusChangedTo(ActivationStatus::Active, $targetDate)) {
     *     echo "Title became active on January 15, 2024";
     * }
     * ```
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
     *
     * @return string The formatted date (Y-m-d) or 'TBD'
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * echo $title->getFormattedDebutDate(); // "2024-01-15" or "TBD"
     * ```
     */
    public function getFormattedDebutDate(): string
    {
        $debutDate = $this->debutedAt();

        return $debutDate?->format('Y-m-d') ?? 'TBD';
    }

    /**
     * Resolve the model class for the status change relation.
     *
     * This method automatically determines the status change model class based on naming
     * conventions. For example, if the parent model is 'Title', it will look for
     * a 'TitleStatusChange' model class.
     *
     * @return class-string<TStatusChange> The fully qualified class name of the status change model
     *
     * @throws RuntimeException If the resolved model class doesn't exist
     *
     * @see fakeStatusChangeModel() For overriding the resolved model class
     *
     * @example
     * For a 'Title' model, this will resolve to 'App\\Models\\Titles\\TitleStatusChange'
     */
    protected function resolveStatusChangeModelClass(): string
    {
        return $this->resolveRelatedModelClass('StatusChange');
    }

    /**
     * Override the resolved model class for testing or customization.
     *
     * This method allows you to override the automatic model class resolution,
     * which is particularly useful for testing scenarios where you might want
     * to use a different model class or mock.
     *
     * @param  class-string<TStatusChange>  $class  The fully qualified class name to use
     *
     * @example
     * ```php
     * // In a test:
     * Title::fakeStatusChangeModel(MockTitleStatusChange::class);
     *
     * // Or for customization:
     * Title::fakeStatusChangeModel(CustomStatusChangeModel::class);
     * ```
     *
     * @see resolveStatusChangeModelClass() For the automatic resolution logic
     */
    public static function fakeStatusChangeModel(string $class): void
    {
        self::cacheRelatedModel('StatusChange', $class);
    }
}
