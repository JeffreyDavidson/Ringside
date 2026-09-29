<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

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
            RosterLifecycleAction::Restore => $individual->trashed(),
        };
    }
}
