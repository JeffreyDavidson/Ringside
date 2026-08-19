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

    public function employ(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Employ, RosterEntityType::Referee, $this->referee, fn () => resolve(EmployAction::class)->handle($this->referee));
    }

    public function release(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Release, RosterEntityType::Referee, $this->referee, fn () => resolve(ReleaseAction::class)->handle($this->referee));
    }

    public function retire(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Retire, RosterEntityType::Referee, $this->referee, fn () => resolve(RetireAction::class)->handle($this->referee));
    }

    public function unretire(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Unretire, RosterEntityType::Referee, $this->referee, fn () => resolve(UnretireAction::class)->handle($this->referee));
    }

    public function suspend(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Suspend, RosterEntityType::Referee, $this->referee, fn () => resolve(SuspendAction::class)->handle($this->referee));
    }

    public function reinstate(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Reinstate, RosterEntityType::Referee, $this->referee, fn () => resolve(ReinstateAction::class)->handle($this->referee));
    }

    public function injure(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Injure, RosterEntityType::Referee, $this->referee, fn () => resolve(InjureAction::class)->handle($this->referee));
    }

    public function clearFromInjury(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::ClearFromInjury, RosterEntityType::Referee, $this->referee, fn () => resolve(ClearFromInjuryAction::class)->handle($this->referee));
    }

    public function restore(): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Restore, RosterEntityType::Referee, $this->referee, fn () => resolve(RestoreAction::class)->handle($this->referee));
    }

    public function render(): View
    {
        return view('livewire.referees.components.actions');
    }
}
