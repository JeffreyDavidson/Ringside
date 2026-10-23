<?php

declare(strict_types=1);

namespace App\Actions\Lifecycle;

use App\Exceptions\Lifecycle\EmploymentDateException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Roster\EmploymentDateRequirements;
use App\Models\Contracts\Employable;
use App\Models\Lifecycle\Employment;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Applies the employment date of an edit form to a wrestler, manager, referee or tag team whose only employment
 * has not started yet: another date moves it, an empty one cancels the hire. Once the employment has started, or
 * when it follows an earlier one, nothing here applies and the caller carries on as before.
 */
class ChangeScheduledEmploymentAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly EmploymentDateRequirements $employmentDates,
    ) {}

    /**
     * @param  Model&Employable<*>  $employable  The record, locked by the caller's transaction
     * @return bool Whether the record had a scheduled employment, so the date was handled here
     *
     * @throws EmploymentDateException When the date is a day before today
     */
    public function handle(Model&Employable $employable, ?Carbon $date): bool
    {
        return DB::transaction(function () use ($employable, $date): bool {
            $scheduled = $employable->soleScheduledEmployment();

            if (! $scheduled instanceof Employment) {
                return false;
            }

            if (! $date instanceof Carbon) {
                $this->employmentPeriods->cancel($employable);

                return true;
            }

            $relatedPromotion = $employable->getRelationValue('promotion');
            $promotion = $relatedPromotion instanceof Promotion ? $relatedPromotion : null;

            // The form holds only the day, so the same day in the promotion's time zone keeps the scheduled time of day.
            $isSameDay = PromotionTimeZoneService::toLocalTime($promotion, $scheduled->started_at)
                ->isSameDay(PromotionTimeZoneService::toLocalTime($promotion, $date));

            if (! $isSameDay) {
                $this->employmentDates->ensureScheduledCanMoveTo($employable, $date);
                $this->employmentPeriods->reschedule($employable, $date);
            }

            return true;
        });
    }
}
