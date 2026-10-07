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
    /**
     * Project the lifecycle state the eligibility checks read, in one query, so rendering
     * the available actions does not run a fallback existence query per check.
     */
    protected function loadLifecycleState(Wrestler|Manager|Referee $individual): void
    {
        $individual->loadExists([...IndividualBuilder::EMPLOYMENT_STATUS_STATE, ...IndividualBuilder::AVAILABILITY_STATE]);
    }

    protected function isEligibleFor(RosterLifecycleAction $action, Wrestler|Manager|Referee $individual): bool
    {
        return match ($action) {
            RosterLifecycleAction::Employ => app(IndividualEmploymentEligibility::class)->canEmploy($individual),
            RosterLifecycleAction::Release => app(IndividualEmploymentEligibility::class)->canRelease($individual),
            RosterLifecycleAction::Suspend => app(IndividualSuspensionEligibility::class)->canSuspend($individual),
            RosterLifecycleAction::Reinstate => app(IndividualSuspensionEligibility::class)->canReinstate($individual),
            RosterLifecycleAction::Injure => app(IndividualInjuryEligibility::class)->canInjure($individual),
            RosterLifecycleAction::ClearFromInjury => app(IndividualInjuryEligibility::class)->canBeClearedFromInjury($individual),
            RosterLifecycleAction::Retire => app(IndividualRetirementEligibility::class)->canRetire($individual),
            RosterLifecycleAction::Unretire => app(IndividualRetirementEligibility::class)->canUnretire($individual),
        };
    }
}
