<?php

declare(strict_types=1);

namespace App\Services\Roster\Individuals;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

final class IndividualRetirementPeriodService
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
    ) {}

    public function start(Wrestler|Manager|Referee $individual, Carbon $retirementDate): void
    {
        if ($individual->isEmployed()) {
            $this->employmentPeriods->end($individual, $retirementDate);
        }

        if ($individual->isSuspended()) {
            $this->suspensionPeriods->end($individual, $retirementDate);
        } elseif ($individual->isInjured()) {
            $this->injuryPeriods->end($individual, $retirementDate);
        }

        $this->retirementPeriods->start($individual, $retirementDate, LifecycleTransitionType::Retired);
    }
}
