<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster;

use App\Exceptions\Lifecycle\EmploymentDateException;
use App\Models\Contracts\Employable;
use App\Models\Lifecycle\Employment;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * What an edit of the employment date may do to a wrestler, manager, referee or tag team. It refuses and never writes:
 * the form rule asks it, and the Update actions call it before they move or ignore a date. Days are compared in the
 * record's promotion time zone, as the form reads them.
 */
final readonly class EmploymentDateRequirements
{
    /**
     * A scheduled employment can move to today or later; an earlier day would start it retroactively.
     *
     * @param  Model&Employable<*>  $employable
     */
    public function movesScheduledIntoThePast(Model&Employable $employable, Carbon $date): bool
    {
        $promotion = $this->promotionOf($employable);

        return PromotionTimeZoneService::toLocalTime($promotion, $date)
            ->lt(PromotionTimeZoneService::toLocalTime($promotion, now())->startOfDay());
    }

    /**
     * Whether the date is the day the current employment started, or the day the first one did (the date the tag
     * team form shows). The form holds only the day, so the time of day an employment started does not matter.
     *
     * @param  Model&Employable<*>  $employable
     */
    public function keepsStartDay(Model&Employable $employable, Employment $current, Carbon $date): bool
    {
        $promotion = $this->promotionOf($employable);
        $day = PromotionTimeZoneService::toLocalTime($promotion, $date);
        $first = $employable->employments()->oldest('started_at')->first();

        return PromotionTimeZoneService::toLocalTime($promotion, $current->started_at)->isSameDay($day)
            || ($first instanceof Employment && PromotionTimeZoneService::toLocalTime($promotion, $first->started_at)->isSameDay($day));
    }

    /**
     * @param  Model&Employable<*>  $employable
     *
     * @throws EmploymentDateException When the day is before today in the promotion
     */
    public function ensureScheduledCanMoveTo(Model&Employable $employable, Carbon $date): void
    {
        if ($this->movesScheduledIntoThePast($employable, $date)) {
            throw EmploymentDateException::scheduledInThePast($employable);
        }
    }

    /**
     * A started employment keeps its start date: nothing moves it, so an edit that names another day is refused
     * instead of being saved without it. A record that is not currently employed has nothing to protect here.
     *
     * @param  Model&Employable<*>  $employable
     *
     * @throws EmploymentDateException When another day than the one employment started is given
     */
    public function ensureCurrentStartIsUnchanged(Model&Employable $employable, Carbon $date): void
    {
        $current = $employable->currentEmployment()->first();

        if ($current instanceof Employment && ! $this->keepsStartDay($employable, $current, $date)) {
            throw EmploymentDateException::currentStartLocked($employable);
        }
    }

    private function promotionOf(Model $employable): ?Promotion
    {
        $promotion = $employable->getRelationValue('promotion');

        return $promotion instanceof Promotion ? $promotion : null;
    }
}
