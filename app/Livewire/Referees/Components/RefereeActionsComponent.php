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
use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Exceptions\Status\CannotBeInjuredException;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Referees\Referee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Referee Actions Component
 *
 * Handles all business actions that can be performed on a referee including
 * employment management, health status changes, and career lifecycle operations.
 * This component is designed to be reusable across different contexts (tables,
 * detail pages, cards, etc.) while maintaining consistent authorization and
 * error handling patterns.
 */
class RefereeActionsComponent extends Component
{
    public Referee $referee;

    public function mount(Referee $referee): void
    {
        $this->referee = $referee;
    }

    /**
     * Employ a referee.
     */
    public function employ(): void
    {
        Gate::authorize('employ', $this->referee);

        try {
            resolve(EmployAction::class)->handle($this->referee);
            $this->dispatch('referee-updated');
            session()->flash('status', 'Referee successfully employed.');
        } catch (CannotBeEmployedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Release a referee.
     */
    public function release(): void
    {
        Gate::authorize('release', $this->referee);

        try {
            resolve(ReleaseAction::class)->handle($this->referee);
            $this->dispatch('referee-updated');
            session()->flash('status', 'Referee successfully released.');
        } catch (CannotBeReleasedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Retire a referee.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->referee);

        try {
            resolve(RetireAction::class)->handle($this->referee);
            $this->dispatch('referee-updated');
            session()->flash('status', 'Referee successfully retired.');
        } catch (CannotBeRetiredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Unretire a referee.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->referee);

        try {
            resolve(UnretireAction::class)->handle($this->referee);
            $this->dispatch('referee-updated');
            session()->flash('status', 'Referee successfully unretired.');
        } catch (CannotBeUnretiredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Suspend a referee.
     */
    public function suspend(): void
    {
        Gate::authorize('suspend', $this->referee);

        try {
            resolve(SuspendAction::class)->handle($this->referee);
            $this->dispatch('referee-updated');
            session()->flash('status', 'Referee successfully suspended.');
        } catch (CannotBeSuspendedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Reinstate a referee.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->referee);

        try {
            resolve(ReinstateAction::class)->handle($this->referee);
            $this->dispatch('referee-updated');
            session()->flash('status', 'Referee successfully reinstated.');
        } catch (CannotBeReinstatedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Injure a referee.
     */
    public function injure(): void
    {
        Gate::authorize('injure', $this->referee);

        try {
            resolve(InjureAction::class)->handle($this->referee);
            $this->dispatch('referee-updated');
            session()->flash('status', 'Referee injury recorded.');
        } catch (CannotBeInjuredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Heal a referee from injury.
     */
    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->referee);

        try {
            resolve(HealAction::class)->handle($this->referee);
            $this->dispatch('referee-updated');
            session()->flash('status', 'Referee cleared from injury.');
        } catch (CannotBeClearedFromInjuryException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Restore a deleted referee.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->referee);

        resolve(RestoreAction::class)->handle($this->referee);
        $this->dispatch('referee-updated');
        session()->flash('status', 'Referee successfully restored.');
    }

    public function render(): View
    {
        return view('livewire.referees.components.referee-actions-component');
    }
}
