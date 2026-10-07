<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final readonly class CareerPeriodCloser
{
    public function __construct(
        private EmploymentPeriodManager $employmentPeriods,
        private InjuryPeriodManager $injuryPeriods,
        private SuspensionPeriodManager $suspensionPeriods,
    ) {}

    /**
     * End the current employment as a Released transition, then close the open suspension or injury.
     *
     * Callers lock the subject and check eligibility first. Employment must be open (the period
     * writer rejects an end date before its start); the availability period closes as a side effect.
     *
     * @param  Model&Employable<*>&Suspendable<*>  $subject
     */
    public function release(Model&Employable&Suspendable $subject, Carbon $date): void
    {
        $this->employmentPeriods->end($subject, $date, LifecycleTransitionType::Released);

        $this->endAvailability($subject, $date);
    }

    /**
     * Close the open employment, suspension or injury without recording a Released transition.
     *
     * Retirement records its own Retired transition, so employment ends here only when open, clamped
     * to its own start date.
     *
     * @param  Model&Employable<*>&Suspendable<*>  $subject
     */
    public function retire(Model&Employable&Suspendable $subject, Carbon $date): void
    {
        if ($subject->currentEmployment()->exists()) {
            $this->employmentPeriods->end($subject, $date, clampToStart: true);
        }

        $this->endAvailability($subject, $date);
    }

    /**
     * A suspension closes first; an injury closes only when there is no suspension (tag teams cannot be injured).
     *
     * @param  Model&Suspendable<*>  $subject
     */
    private function endAvailability(Model&Suspendable $subject, Carbon $date): void
    {
        if ($subject->currentSuspension()->exists()) {
            $this->suspensionPeriods->end($subject, $date, clampToStart: true);

            return;
        }

        if ($subject instanceof Injurable && $subject->currentInjury()->exists()) {
            $this->injuryPeriods->end($subject, $date, clampToStart: true);
        }
    }
}
