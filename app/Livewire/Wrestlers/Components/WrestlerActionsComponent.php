<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Components;

use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\HealAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RestoreAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Exceptions\Status\CannotBeInjuredException;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Wrestler Actions Component
 *
 * Handles all business actions that can be performed on a wrestler including
 * employment management, health status changes, and career lifecycle operations.
 * This component is designed to be reusable across different contexts (tables,
 * detail pages, cards, etc.) while maintaining consistent authorization and
 * error handling patterns.
 */
class WrestlerActionsComponent extends Component
{
    public Wrestler $wrestler;

    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    /**
     * Employ a wrestler.
     */
    public function employ(): void
    {
        Gate::authorize('employ', $this->wrestler);

        try {
            resolve(EmployAction::class)->handle($this->wrestler);
            $this->dispatch('wrestler-updated');
            session()->flash('status', 'Wrestler successfully employed.');
        } catch (CannotBeEmployedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Release a wrestler.
     */
    public function release(): void
    {
        Gate::authorize('release', $this->wrestler);

        try {
            resolve(ReleaseAction::class)->handle($this->wrestler);
            $this->dispatch('wrestler-updated');
            session()->flash('status', 'Wrestler successfully released.');
        } catch (CannotBeReleasedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Retire a wrestler.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->wrestler);

        try {
            resolve(RetireAction::class)->handle($this->wrestler);
            $this->dispatch('wrestler-updated');
            session()->flash('status', 'Wrestler successfully retired.');
        } catch (CannotBeRetiredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Unretire a wrestler.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->wrestler);

        try {
            resolve(UnretireAction::class)->handle($this->wrestler);
            $this->dispatch('wrestler-updated');
            session()->flash('status', 'Wrestler successfully unretired.');
        } catch (CannotBeUnretiredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Suspend a wrestler.
     */
    public function suspend(): void
    {
        Gate::authorize('suspend', $this->wrestler);

        try {
            resolve(SuspendAction::class)->handle($this->wrestler);
            $this->dispatch('wrestler-updated');
            session()->flash('status', 'Wrestler successfully suspended.');
        } catch (CannotBeSuspendedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Reinstate a wrestler.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->wrestler);

        try {
            resolve(ReinstateAction::class)->handle($this->wrestler);
            $this->dispatch('wrestler-updated');
            session()->flash('status', 'Wrestler successfully reinstated.');
        } catch (CannotBeReinstatedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Injure a wrestler.
     */
    public function injure(): void
    {
        Gate::authorize('injure', $this->wrestler);

        try {
            resolve(InjureAction::class)->handle($this->wrestler);
            $this->dispatch('wrestler-updated');
            session()->flash('status', 'Wrestler injury recorded.');
        } catch (CannotBeInjuredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Heal a wrestler from injury.
     */
    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->wrestler);

        try {
            resolve(HealAction::class)->handle($this->wrestler);
            $this->dispatch('wrestler-updated');
            session()->flash('status', 'Wrestler cleared from injury.');
        } catch (CannotBeClearedFromInjuryException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Restore a deleted wrestler.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->wrestler);

        resolve(RestoreAction::class)->handle($this->wrestler);
        $this->dispatch('wrestler-updated');
        session()->flash('status', 'Wrestler successfully restored.');
    }

    public function render(): View
    {
        return view('livewire.wrestlers.components.wrestler-actions-component');
    }
}
