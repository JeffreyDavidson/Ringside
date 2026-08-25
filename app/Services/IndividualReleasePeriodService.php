<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

final class IndividualReleasePeriodService
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
    ) {}

    public function end(Wrestler|Manager|Referee $individual, Carbon $releaseDate): void
    {
        $this->employmentPeriods->end($individual, $releaseDate, LifecycleTransitionType::Released);

        if ($individual->isSuspended()) {
            $this->suspensionPeriods->end($individual, $releaseDate);
        } elseif ($individual->isInjured()) {
            $this->injuryPeriods->end($individual, $releaseDate);
        }
    }
}
