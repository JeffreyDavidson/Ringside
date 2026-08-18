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

    public function employ(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Employ, RosterEntityType::Manager, $this->manager, fn () => resolve(EmployAction::class)->handle($this->manager));
    }

    public function release(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Release, RosterEntityType::Manager, $this->manager, fn () => resolve(ReleaseAction::class)->handle($this->manager));
    }

    public function retire(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Retire, RosterEntityType::Manager, $this->manager, fn () => resolve(RetireAction::class)->handle($this->manager));
    }

    public function unretire(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Unretire, RosterEntityType::Manager, $this->manager, fn () => resolve(UnretireAction::class)->handle($this->manager));
    }

    public function suspend(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Suspend, RosterEntityType::Manager, $this->manager, fn () => resolve(SuspendAction::class)->handle($this->manager));
    }

    public function reinstate(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Reinstate, RosterEntityType::Manager, $this->manager, fn () => resolve(ReinstateAction::class)->handle($this->manager));
    }

    public function injure(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Injure, RosterEntityType::Manager, $this->manager, fn () => resolve(InjureAction::class)->handle($this->manager));
    }

    public function clearFromInjury(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::ClearFromInjury, RosterEntityType::Manager, $this->manager, fn () => resolve(ClearFromInjuryAction::class)->handle($this->manager));
    }

    public function restore(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Restore, RosterEntityType::Manager, $this->manager, fn () => resolve(RestoreAction::class)->handle($this->manager));
    }

    public function render(): View
    {
        return view('livewire.managers.components.actions');
    }
}
