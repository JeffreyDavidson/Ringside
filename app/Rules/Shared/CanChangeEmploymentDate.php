<?php

declare(strict_types=1);

namespace App\Rules\Shared;

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use App\Models\Contracts\Employable;
use App\Models\Lifecycle\Employment;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionTimeZoneService;
use App\Support\ModelName;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Validates that an employment date can be changed for any employable model.
 */
class CanChangeEmploymentDate implements ValidationRule
{
    /**
     * @param  (Model&Employable<*>)|null  $model
     */
    public function __construct(private readonly (Model&Employable)|null $model) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->model === null) {
            return;
        }

        if (! $value instanceof DateTimeInterface && ! is_float($value) && ! is_int($value) && ! is_string($value)) {
            $fail(__('core.validation.employment_date_invalid'));

            return;
        }

        $relatedPromotion = $this->model->getRelationValue('promotion');
        $promotion = $relatedPromotion instanceof Promotion ? $relatedPromotion : null;

        // A typed date means that day in the model's promotion (the application time zone without one), the way the form reads it; other values are instants.
        $targetDate = is_string($value)
            ? PromotionTimeZoneService::parseLocalTime($promotion, $value)
            : Carbon::parse($value);

        $currentEmployment = $this->model->currentEmployment()->first();

        if ($currentEmployment === null) {
            // A scheduled employment can move to today or later; an earlier day would start it retroactively.
            $isBeforeToday = $this->model->soleScheduledEmployment() instanceof Employment
                && PromotionTimeZoneService::toLocalTime($promotion, $targetDate)->lt(PromotionTimeZoneService::toLocalTime($promotion, now())->startOfDay());

            if ($isBeforeToday) {
                $fail(__('core.validation.employment_date_scheduled_past'));
            }

            return;
        }

        // The form only holds the day, so the day employment started stays valid whatever time of day it started.
        $startDay = PromotionTimeZoneService::toLocalTime($promotion, $currentEmployment->started_at);

        if ($startDay->isSameDay(PromotionTimeZoneService::toLocalTime($promotion, $targetDate))) {
            return;
        }

        $query = $this->model->employments()->getQuery();
        LifecyclePeriodBuilder::constrainToActiveOn($query, $targetDate);

        if (! $query->exists()) {
            $fail(__('core.validation.employment_date_employed', ['name' => ModelName::of($this->model)]));
        }
    }
}
