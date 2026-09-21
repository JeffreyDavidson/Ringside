<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Components;

use App\Actions\Wrestlers\ClearFromInjuryAction;
use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RestoreAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Actions extends Component
{
    use ExecutesRosterActions;

    public Wrestler $wrestler;

    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    public function employ(EmployAction $employAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Employ, RosterEntityType::Wrestler, $this->wrestler, fn () => $employAction->handle($this->wrestler));
    }

    public function release(ReleaseAction $releaseAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Release, RosterEntityType::Wrestler, $this->wrestler, fn () => $releaseAction->handle($this->wrestler));
    }

    public function retire(RetireAction $retireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Retire, RosterEntityType::Wrestler, $this->wrestler, fn () => $retireAction->handle($this->wrestler));
    }

    public function unretire(UnretireAction $unretireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Unretire, RosterEntityType::Wrestler, $this->wrestler, fn () => $unretireAction->handle($this->wrestler));
    }

    public function suspend(SuspendAction $suspendAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Suspend, RosterEntityType::Wrestler, $this->wrestler, fn () => $suspendAction->handle($this->wrestler));
    }

    public function reinstate(ReinstateAction $reinstateAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Reinstate, RosterEntityType::Wrestler, $this->wrestler, fn () => $reinstateAction->handle($this->wrestler));
    }

    public function injure(InjureAction $injureAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Injure, RosterEntityType::Wrestler, $this->wrestler, fn () => $injureAction->handle($this->wrestler));
    }

    public function clearFromInjury(ClearFromInjuryAction $clearFromInjuryAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::ClearFromInjury, RosterEntityType::Wrestler, $this->wrestler, fn () => $clearFromInjuryAction->handle($this->wrestler));
    }

    public function restore(RestoreAction $restoreAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Restore, RosterEntityType::Wrestler, $this->wrestler, fn () => $restoreAction->handle($this->wrestler));
    }

    public function render(): View
    {
        return view('livewire.wrestlers.components.actions');
    }
}
