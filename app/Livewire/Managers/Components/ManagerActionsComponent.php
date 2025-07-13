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
use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Exceptions\Status\CannotBeInjuredException;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Managers\Manager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Manager Actions Component
 *
 * Handles all business actions that can be performed on a manager including
 * employment management, health status changes, and career lifecycle operations.
 * This component is designed to be reusable across different contexts (tables,
 * detail pages, cards, etc.) while maintaining consistent authorization and
 * error handling patterns.
 */
class ManagerActionsComponent extends Component
{
    public Manager $manager;

    public function mount(Manager $manager): void
    {
        $this->manager = $manager;
    }

    /**
     * Employ a manager.
     */
    public function employ(): void
    {
        Gate::authorize('employ', $this->manager);

        try {
            resolve(EmployAction::class)->handle($this->manager);
            $this->dispatch('manager-updated');
            session()->flash('status', 'Manager successfully employed.');
        } catch (CannotBeEmployedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Release a manager.
     */
    public function release(): void
    {
        Gate::authorize('release', $this->manager);

        try {
            resolve(ReleaseAction::class)->handle($this->manager);
            $this->dispatch('manager-updated');
            session()->flash('status', 'Manager successfully released.');
        } catch (CannotBeReleasedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Retire a manager.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->manager);

        try {
            resolve(RetireAction::class)->handle($this->manager);
            $this->dispatch('manager-updated');
            session()->flash('status', 'Manager successfully retired.');
        } catch (CannotBeRetiredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Unretire a manager.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->manager);

        try {
            resolve(UnretireAction::class)->handle($this->manager);
            $this->dispatch('manager-updated');
            session()->flash('status', 'Manager successfully unretired.');
        } catch (CannotBeUnretiredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Suspend a manager.
     */
    public function suspend(): void
    {
        Gate::authorize('suspend', $this->manager);

        try {
            resolve(SuspendAction::class)->handle($this->manager);
            $this->dispatch('manager-updated');
            session()->flash('status', 'Manager successfully suspended.');
        } catch (CannotBeSuspendedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Reinstate a manager.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->manager);

        try {
            resolve(ReinstateAction::class)->handle($this->manager);
            $this->dispatch('manager-updated');
            session()->flash('status', 'Manager successfully reinstated.');
        } catch (CannotBeReinstatedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Injure a manager.
     */
    public function injure(): void
    {
        Gate::authorize('injure', $this->manager);

        try {
            resolve(InjureAction::class)->handle($this->manager);
            $this->dispatch('manager-updated');
            session()->flash('status', 'Manager injury recorded.');
        } catch (CannotBeInjuredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Heal a manager from injury.
     */
    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->manager);

        try {
            resolve(HealAction::class)->handle($this->manager);
            $this->dispatch('manager-updated');
            session()->flash('status', 'Manager cleared from injury.');
        } catch (CannotBeClearedFromInjuryException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Restore a deleted manager.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->manager);

        resolve(RestoreAction::class)->handle($this->manager);
        $this->dispatch('manager-updated');
        session()->flash('status', 'Manager successfully restored.');
    }

    public function render(): View
    {
        return view('livewire.managers.components.manager-actions-component');
    }
}
