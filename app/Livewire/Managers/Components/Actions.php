<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Components;

use App\Actions\Managers\EmployAction;
use App\Actions\Managers\HealAction;
use App\Actions\Managers\InjureAction;
use App\Actions\Managers\ReinstateAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\RestoreAction;
use App\Actions\Managers\RetireAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\Managers\Manager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('employ', $this->manager);
        $this->executeRosterAction('employed', 'manager', fn () => resolve(EmployAction::class)->handle($this->manager));
    }

    public function release(): void
    {
        Gate::authorize('release', $this->manager);
        $this->executeRosterAction('released', 'manager', fn () => resolve(ReleaseAction::class)->handle($this->manager));
    }

    public function retire(): void
    {
        Gate::authorize('retire', $this->manager);
        $this->executeRosterAction('retired', 'manager', fn () => resolve(RetireAction::class)->handle($this->manager));
    }

    public function unretire(): void
    {
        Gate::authorize('unretire', $this->manager);
        $this->executeRosterAction('unretired', 'manager', fn () => resolve(UnretireAction::class)->handle($this->manager));
    }

    public function suspend(): void
    {
        Gate::authorize('suspend', $this->manager);
        $this->executeRosterAction('suspended', 'manager', fn () => resolve(SuspendAction::class)->handle($this->manager));
    }

    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->manager);
        $this->executeRosterAction('reinstated', 'manager', fn () => resolve(ReinstateAction::class)->handle($this->manager));
    }

    public function injure(): void
    {
        Gate::authorize('injure', $this->manager);
        $this->executeRosterAction('injured', 'manager', fn () => resolve(InjureAction::class)->handle($this->manager));
    }

    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->manager);
        $this->executeRosterAction('healed', 'manager', fn () => resolve(HealAction::class)->handle($this->manager));
    }

    public function restore(): void
    {
        Gate::authorize('restore', $this->manager);
        $this->executeRosterAction('restored', 'manager', fn () => resolve(RestoreAction::class)->handle($this->manager));
    }

    public function render(): View
    {
        return view('livewire.managers.components.actions');
    }
}
