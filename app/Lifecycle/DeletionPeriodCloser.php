<?php

declare(strict_types=1);

namespace App\Lifecycle;

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
        if ($subject->isEmployed()) {
            $this->employmentPeriods->end($subject, $date);
        }

        if ($subject->isRetired()) {
            $this->retirementPeriods->end($subject, $date);
        }

        if ($subject->isSuspended()) {
            $this->suspensionPeriods->end($subject, $date);
        }

        if ($subject->isInjured()) {
            $this->injuryPeriods->end($subject, $date);
        }
    }
}
