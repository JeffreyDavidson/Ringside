<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Components;

use App\Actions\Referees\ClearFromInjuryAction;
use App\Actions\Referees\EmployAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReinstateAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RestoreAction;
use App\Actions\Referees\RetireAction;
use App\Actions\Referees\SuspendAction;
use App\Actions\Referees\UnretireAction;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\Referees\Referee;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Actions extends Component
{
    use ExecutesRosterActions;

    public Referee $referee;

    public function mount(Referee $referee): void
    {
        $this->referee = $referee;
    }

    public function employ(EmployAction $employAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Employ, RosterEntityType::Referee, $this->referee, fn () => $employAction->handle($this->referee));
    }

    public function release(ReleaseAction $releaseAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Release, RosterEntityType::Referee, $this->referee, fn () => $releaseAction->handle($this->referee));
    }

    public function retire(RetireAction $retireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Retire, RosterEntityType::Referee, $this->referee, fn () => $retireAction->handle($this->referee));
    }

    public function unretire(UnretireAction $unretireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Unretire, RosterEntityType::Referee, $this->referee, fn () => $unretireAction->handle($this->referee));
    }

    public function suspend(SuspendAction $suspendAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Suspend, RosterEntityType::Referee, $this->referee, fn () => $suspendAction->handle($this->referee));
    }

    public function reinstate(ReinstateAction $reinstateAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Reinstate, RosterEntityType::Referee, $this->referee, fn () => $reinstateAction->handle($this->referee));
    }

    public function injure(InjureAction $injureAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Injure, RosterEntityType::Referee, $this->referee, fn () => $injureAction->handle($this->referee));
    }

    public function clearFromInjury(ClearFromInjuryAction $clearFromInjuryAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::ClearFromInjury, RosterEntityType::Referee, $this->referee, fn () => $clearFromInjuryAction->handle($this->referee));
    }

    public function restore(RestoreAction $restoreAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Restore, RosterEntityType::Referee, $this->referee, fn () => $restoreAction->handle($this->referee));
    }

    public function render(): View
    {
        return view('livewire.referees.components.actions');
    }
}
