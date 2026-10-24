<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Data\Titles\TitleData;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Titles\NameTakenException;
use App\Lifecycle\Naming\GuardedNameRequirements;
use App\Lifecycle\Naming\RecordNameLock;
use App\Lifecycle\Periods\LifecycleTransitionRecorder;
use App\Lifecycle\Titles\TitleTypeEligibility;
use App\Models\Promotions\Promotion;
use App\Models\Scopes\PromotionContextScope;
use App\Models\Titles\Title;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(
        private readonly DebutAction $debut,
        private readonly RecordNameLock $nameLock,
        private readonly LifecycleTransitionRecorder $transitionRecorder,
        private readonly GuardedNameRequirements $nameRequirements,
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

            $this->nameRequirements->ensureAvailable(
                Title::query()
                    ->withoutGlobalScope(PromotionContextScope::class)
                    ->withTrashed()
                    ->where('promotion_id', $lockedTitle->promotion_id),
                'name',
                $titleData->name,
                fn (): NameTakenException => NameTakenException::name($titleData->name),
                except: $lockedTitle,
            );

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
     * The scheduled period moves in place so the title keeps exactly one period. The move is recorded as a new
     * Debuted transition at the new date that notes the date it moved from, as an employment reschedule is; the
     * earlier transition stays, because transitions are an immutable trail. Active, pulled, and retired titles
     * are left alone.
     */
    private function applyDebutDate(Title $title, Carbon $debutDate): void
    {
        $activityPeriods = $title->activityPeriods()->lockForUpdate()->get();

        if ($activityPeriods->isEmpty()) {
            $this->debut->handle($title, $debutDate);

            return;
        }

        $scheduledPeriod = $activityPeriods->first();

        // The debut date is a day in the promotion's time zone, so "same day" is judged there, not in UTC.
        $isSameLocalDay = PromotionTimeZoneService::toLocalTime($title->promotion, $scheduledPeriod->started_at)
            ->isSameDay(PromotionTimeZoneService::toLocalTime($title->promotion, $debutDate));

        if ($activityPeriods->count() > 1 || ! $scheduledPeriod->started_at->isFuture() || $isSameLocalDay) {
            return;
        }

        $scheduledFor = $scheduledPeriod->started_at;

        $scheduledPeriod->update(['started_at' => $debutDate]);
        $this->transitionRecorder->record(
            $title,
            LifecycleDimension::Activity,
            LifecycleTransitionType::Debuted,
            $debutDate,
            ['rescheduled_from' => $scheduledFor->toIso8601String()],
        );
    }
}
