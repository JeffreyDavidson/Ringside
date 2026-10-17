<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Builders\Roster\IndividualBuilder;
use App\Enums\Roster\RosterLifecycleAction;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Lifecycle\Roster\Individuals\IndividualInjuryEligibility;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Lifecycle\Roster\Individuals\IndividualSuspensionEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

trait ChecksIndividualLifecycleEligibility
{
    private IndividualEmploymentEligibility $employmentEligibility;

    private IndividualSuspensionEligibility $suspensionEligibility;

    private IndividualInjuryEligibility $injuryEligibility;

    private IndividualRetirementEligibility $retirementEligibility;

    /**
     * Livewire calls this trait boot hook with container-injected arguments on every request, which lets the
     * trait share the eligibility checks without resolving them from the container inline.
     */
    public function bootChecksIndividualLifecycleEligibility(
        IndividualEmploymentEligibility $employmentEligibility,
        IndividualSuspensionEligibility $suspensionEligibility,
        IndividualInjuryEligibility $injuryEligibility,
        IndividualRetirementEligibility $retirementEligibility,
    ): void {
        $this->employmentEligibility = $employmentEligibility;
        $this->suspensionEligibility = $suspensionEligibility;
        $this->injuryEligibility = $injuryEligibility;
        $this->retirementEligibility = $retirementEligibility;
    }

    /**
     * Project the lifecycle state the eligibility checks read, in one query, so rendering
     * the available actions does not run a fallback existence query per check.
     */
    protected function loadLifecycleState(Wrestler|Manager|Referee $individual): void
    {
        $individual->loadExists(IndividualBuilder::ROSTER_STATE);
    }

    protected function isEligibleFor(RosterLifecycleAction $action, Wrestler|Manager|Referee $individual): bool
    {
        return match ($action) {
            RosterLifecycleAction::Employ => $this->employmentEligibility->canEmploy($individual),
            RosterLifecycleAction::Release => $this->employmentEligibility->canRelease($individual),
            RosterLifecycleAction::Suspend => $this->suspensionEligibility->canSuspend($individual),
            RosterLifecycleAction::Reinstate => $this->suspensionEligibility->canReinstate($individual),
            RosterLifecycleAction::Injure => $this->injuryEligibility->canInjure($individual),
            RosterLifecycleAction::ClearFromInjury => $this->injuryEligibility->canBeClearedFromInjury($individual),
            RosterLifecycleAction::Retire => $this->retirementEligibility->canRetire($individual),
            RosterLifecycleAction::Unretire => $this->retirementEligibility->canUnretire($individual),
        };
    }
}
