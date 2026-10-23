<?php

declare(strict_types=1);

namespace App\Rules\Shared;

use App\Lifecycle\Roster\EmploymentDateRequirements;
use App\Models\Contracts\Employable;
use App\Models\Lifecycle\Employment;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionTimeZoneService;
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
    public function __construct(
        private readonly (Model&Employable)|null $model,
        private readonly EmploymentDateRequirements $requirements = new EmploymentDateRequirements,
    ) {}

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
            if ($this->model->soleScheduledEmployment() instanceof Employment && $this->requirements->movesScheduledIntoThePast($this->model, $targetDate)) {
                $fail(__('core.validation.employment_date_scheduled_past'));
            }

            return;
        }

        if (! $this->requirements->keepsStartDay($this->model, $currentEmployment, $targetDate)) {
            $fail(__('core.validation.employment_date_employed', ['name' => $this->getModelName($this->model)]));
        }
    }

    /**
     * The name a person reads for the record: `name`, else `full_name` (referees and managers).
     */
    private function getModelName(Model $model): string
    {
        $name = $model->getAttribute('name') ?? $model->getAttribute('full_name');

        return is_string($name) ? $name : '';
    }
}
