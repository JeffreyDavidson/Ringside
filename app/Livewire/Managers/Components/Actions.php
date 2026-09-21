<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Components;

use App\Actions\Managers\ClearFromInjuryAction;
use App\Actions\Managers\EmployAction;
use App\Actions\Managers\InjureAction;
use App\Actions\Managers\ReinstateAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\RestoreAction;
use App\Actions\Managers\RetireAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\Managers\Manager;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Actions extends Component
{
    use ExecutesRosterActions;

    public Manager $manager;

    public function mount(Manager $manager): void
    {
        $this->manager = $manager;
    }

    public function employ(EmployAction $employAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Employ, RosterEntityType::Manager, $this->manager, fn () => $employAction->handle($this->manager));
    }

    public function release(ReleaseAction $releaseAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Release, RosterEntityType::Manager, $this->manager, fn () => $releaseAction->handle($this->manager));
    }

    public function retire(RetireAction $retireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Retire, RosterEntityType::Manager, $this->manager, fn () => $retireAction->handle($this->manager));
    }

    public function unretire(UnretireAction $unretireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Unretire, RosterEntityType::Manager, $this->manager, fn () => $unretireAction->handle($this->manager));
    }

    public function suspend(SuspendAction $suspendAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Suspend, RosterEntityType::Manager, $this->manager, fn () => $suspendAction->handle($this->manager));
    }

    public function reinstate(ReinstateAction $reinstateAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Reinstate, RosterEntityType::Manager, $this->manager, fn () => $reinstateAction->handle($this->manager));
    }

    public function injure(InjureAction $injureAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Injure, RosterEntityType::Manager, $this->manager, fn () => $injureAction->handle($this->manager));
    }

    public function clearFromInjury(ClearFromInjuryAction $clearFromInjuryAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::ClearFromInjury, RosterEntityType::Manager, $this->manager, fn () => $clearFromInjuryAction->handle($this->manager));
    }

    public function restore(RestoreAction $restoreAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Restore, RosterEntityType::Manager, $this->manager, fn () => $restoreAction->handle($this->manager));
    }

    public function render(): View
    {
        return view('livewire.managers.components.actions');
    }
}
