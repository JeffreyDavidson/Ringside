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
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\Referees\Referee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('employ', $this->referee);
        $this->executeRosterAction('employed', RosterEntityType::Referee, fn () => resolve(EmployAction::class)->handle($this->referee));
    }

    public function release(): void
    {
        Gate::authorize('release', $this->referee);
        $this->executeRosterAction('released', RosterEntityType::Referee, fn () => resolve(ReleaseAction::class)->handle($this->referee));
    }

    public function retire(): void
    {
        Gate::authorize('retire', $this->referee);
        $this->executeRosterAction('retired', RosterEntityType::Referee, fn () => resolve(RetireAction::class)->handle($this->referee));
    }

    public function unretire(): void
    {
        Gate::authorize('unretire', $this->referee);
        $this->executeRosterAction('unretired', RosterEntityType::Referee, fn () => resolve(UnretireAction::class)->handle($this->referee));
    }

    public function suspend(): void
    {
        Gate::authorize('suspend', $this->referee);
        $this->executeRosterAction('suspended', RosterEntityType::Referee, fn () => resolve(SuspendAction::class)->handle($this->referee));
    }

    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->referee);
        $this->executeRosterAction('reinstated', RosterEntityType::Referee, fn () => resolve(ReinstateAction::class)->handle($this->referee));
    }

    public function injure(): void
    {
        Gate::authorize('injure', $this->referee);
        $this->executeRosterAction('injured', RosterEntityType::Referee, fn () => resolve(InjureAction::class)->handle($this->referee));
    }

    public function clearFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->referee);
        $this->executeRosterAction('cleared_from_injury', RosterEntityType::Referee, fn () => resolve(ClearFromInjuryAction::class)->handle($this->referee));
    }

    public function restore(): void
    {
        Gate::authorize('restore', $this->referee);
        $this->executeRosterAction('restored', RosterEntityType::Referee, fn () => resolve(RestoreAction::class)->handle($this->referee));
    }

    public function render(): View
    {
        return view('livewire.referees.components.actions');
    }
}
