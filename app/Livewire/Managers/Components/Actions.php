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
use App\Enums\Roster\RosterEntityType;
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
        $this->executeRosterAction('employed', RosterEntityType::Manager, fn () => resolve(EmployAction::class)->handle($this->manager));
    }

    public function release(): void
    {
        Gate::authorize('release', $this->manager);
        $this->executeRosterAction('released', RosterEntityType::Manager, fn () => resolve(ReleaseAction::class)->handle($this->manager));
    }

    public function retire(): void
    {
        Gate::authorize('retire', $this->manager);
        $this->executeRosterAction('retired', RosterEntityType::Manager, fn () => resolve(RetireAction::class)->handle($this->manager));
    }

    public function unretire(): void
    {
        Gate::authorize('unretire', $this->manager);
        $this->executeRosterAction('unretired', RosterEntityType::Manager, fn () => resolve(UnretireAction::class)->handle($this->manager));
    }

    public function suspend(): void
    {
        Gate::authorize('suspend', $this->manager);
        $this->executeRosterAction('suspended', RosterEntityType::Manager, fn () => resolve(SuspendAction::class)->handle($this->manager));
    }

    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->manager);
        $this->executeRosterAction('reinstated', RosterEntityType::Manager, fn () => resolve(ReinstateAction::class)->handle($this->manager));
    }

    public function injure(): void
    {
        Gate::authorize('injure', $this->manager);
        $this->executeRosterAction('injured', RosterEntityType::Manager, fn () => resolve(InjureAction::class)->handle($this->manager));
    }

    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->manager);
        $this->executeRosterAction('healed', RosterEntityType::Manager, fn () => resolve(HealAction::class)->handle($this->manager));
    }

    public function restore(): void
    {
        Gate::authorize('restore', $this->manager);
        $this->executeRosterAction('restored', RosterEntityType::Manager, fn () => resolve(RestoreAction::class)->handle($this->manager));
    }

    public function render(): View
    {
        return view('livewire.managers.components.actions');
    }
}
