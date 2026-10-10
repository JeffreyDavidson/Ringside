<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Data\Titles\TitleData;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Titles\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Lifecycle\Titles\TitleTypeEligibility;
use App\Models\Scopes\PromotionContextScope;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(
        private readonly DebutAction $debut,
        private readonly RecordNameLock $nameLock,
    ) {}

    /**
     * Update a title.
     *
     * This handles the complete title update workflow:
     * - Updates title information (name, championship type); the type is locked once the title has reigns or bookings
     * - Rejects a name another title of the promotion already uses (deleted ones count, as they do for the form rule);
     *   nothing in the database keeps it unique, so it first takes the name lock, before the title's own row lock
     * - Handles conditional debut if debut_date is provided and title is not active
     * - Maintains championship integrity and lineage throughout the update process
     * - Preserves all historical championship and status records
     *
     * @param  Title  $title  The title to update
     * @param  TitleData  $titleData  The updated title information
     * @return Title The updated title instance
     *
     * @throws NameTakenException When another title of the promotion already has the name
     */
    public function handle(Title $title, TitleData $titleData): Title
    {
        return DB::transaction(function () use ($title, $titleData): Title {
            $this->nameLock->lock(GuardedName::TitleName, $title->promotion_id, $titleData->name);

            $lockedTitle = $title->refreshForUpdate();

            $nameTaken = Title::query()
                ->withoutGlobalScope(PromotionContextScope::class)
                ->withTrashed()
                ->whereNameInPromotion($titleData->name, $lockedTitle->promotion_id)
                ->whereKeyNot($lockedTitle->getKey())
                ->exists();

            if ($nameTaken) {
                throw NameTakenException::name($titleData->name);
            }

            TitleTypeEligibility::ensureCanChange($lockedTitle, $titleData->type);

            $lockedTitle->update([
                'name' => $titleData->name,
                'type' => $titleData->type,
            ]);

            // Handle conditional debut creation - only debut titles that have never debuted before
            // Note: This will not reactivate pulled titles - use ReinstateAction for that
            if (! is_null($titleData->debut_date)) {
                $this->applyDebutDate($lockedTitle, $titleData->debut_date);
            }

            return $lockedTitle;
        });
    }

    /**
     * Debut a title that has no activity history, or move the date of a debut that is only scheduled.
     *
     * The scheduled period and its pending Debuted transition are updated in place so the title keeps
     * exactly one period and one Debuted record. Active, pulled, and retired titles are left alone.
     */
    private function applyDebutDate(Title $title, Carbon $debutDate): void
    {
        $activityPeriods = $title->activityPeriods()->lockForUpdate()->get();

        if ($activityPeriods->isEmpty()) {
            $this->debut->handle($title, $debutDate);

            return;
        }

        $scheduledPeriod = $activityPeriods->first();

        if ($activityPeriods->count() > 1 || ! $scheduledPeriod->started_at->isFuture() || $scheduledPeriod->started_at->isSameDay($debutDate)) {
            return;
        }

        $scheduledPeriod->update(['started_at' => $debutDate]);
        $title->lifecycleTransitions()
            ->where('dimension', LifecycleDimension::Activity)
            ->where('transition', LifecycleTransitionType::Debuted)
            ->update(['effective_at' => $debutDate]);
    }
}
