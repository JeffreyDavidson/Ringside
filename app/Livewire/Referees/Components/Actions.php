<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Components;

use App\Actions\Referees\EmployAction;
use App\Actions\Referees\HealAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReinstateAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RestoreAction;
use App\Actions\Referees\RetireAction;
use App\Actions\Referees\SuspendAction;
use App\Actions\Referees\UnretireAction;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Referees\Referee;
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
        $this->executeRosterAction('employed', 'referee', fn () => resolve(EmployAction::class)->handle($this->referee));
    }

    public function release(): void
    {
        Gate::authorize('release', $this->referee);
        $this->executeRosterAction('released', 'referee', fn () => resolve(ReleaseAction::class)->handle($this->referee));
    }

    public function retire(): void
    {
        Gate::authorize('retire', $this->referee);
        $this->executeRosterAction('retired', 'referee', fn () => resolve(RetireAction::class)->handle($this->referee));
    }

    public function unretire(): void
    {
        Gate::authorize('unretire', $this->referee);
        $this->executeRosterAction('unretired', 'referee', fn () => resolve(UnretireAction::class)->handle($this->referee));
    }

    public function suspend(): void
    {
        Gate::authorize('suspend', $this->referee);
        $this->executeRosterAction('suspended', 'referee', fn () => resolve(SuspendAction::class)->handle($this->referee));
    }

    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->referee);
        $this->executeRosterAction('reinstated', 'referee', fn () => resolve(ReinstateAction::class)->handle($this->referee));
    }

    public function injure(): void
    {
        Gate::authorize('injure', $this->referee);
        $this->executeRosterAction('injured', 'referee', fn () => resolve(InjureAction::class)->handle($this->referee));
    }

    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->referee);
        $this->executeRosterAction('healed', 'referee', fn () => resolve(HealAction::class)->handle($this->referee));
    }

    public function restore(): void
    {
        Gate::authorize('restore', $this->referee);
        $this->executeRosterAction('restored', 'referee', fn () => resolve(RestoreAction::class)->handle($this->referee));
    }

    public function render(): View
    {
        return view('livewire.referees.components.actions');
    }
}
