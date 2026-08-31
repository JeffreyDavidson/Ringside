<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class DeletionPeriodCloser
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
    ) {}

    /**
     * @param  Model&Employable<*>&Injurable<*>&Retirable<*>&Suspendable<*>  $subject
     */
    public function close(Model&Employable&Injurable&Retirable&Suspendable $subject, Carbon $date): void
    {
        if ($subject->currentEmployment()->exists()) {
            $this->employmentPeriods->end($subject, $date);
        }

        if ($subject->currentRetirement()->exists()) {
            $this->retirementPeriods->end($subject, $date);
        }

        if ($subject->currentSuspension()->exists()) {
            $this->suspensionPeriods->end($subject, $date);
        }

        if ($subject->currentInjury()->exists()) {
            $this->injuryPeriods->end($subject, $date);
        }
    }
}
