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
use App\Livewire\Concerns\ExecutesActionsWithContext;
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
class Actions extends Component
{
    use ExecutesActionsWithContext;

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

        $this->executeActionWithContext(
            'employed',
            EmployAction::class,
            $this->manager,
            'manager',
            fn () => [
                'manager_current_status' => $this->manager->status,
                'manager_is_employed' => $this->manager->isEmployed(),
                'manager_is_suspended' => $this->manager->isSuspended(),
                'manager_is_retired' => $this->manager->isRetired(),
                'manager_is_injured' => $this->manager->isInjured(),
            ]
        );
    }

    /**
     * Release a manager.
     */
    public function release(): void
    {
        Gate::authorize('release', $this->manager);

        $this->executeActionWithContext(
            'released',
            ReleaseAction::class,
            $this->manager,
            'manager',
            fn () => [
                'manager_current_status' => $this->manager->status,
                'manager_is_employed' => $this->manager->isEmployed(),
                'manager_is_suspended' => $this->manager->isSuspended(),
            ]
        );
    }

    /**
     * Retire a manager.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->manager);

        $this->executeActionWithContext(
            'retired',
            RetireAction::class,
            $this->manager,
            'manager',
            fn () => [
                'manager_current_status' => $this->manager->status,
                'manager_is_employed' => $this->manager->isEmployed(),
                'manager_is_suspended' => $this->manager->isSuspended(),
            ]
        );
    }

    /**
     * Unretire a manager.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->manager);

        $this->executeActionWithContext(
            'unretired',
            UnretireAction::class,
            $this->manager,
            'manager',
            fn () => [
                'manager_current_status' => $this->manager->status,
                'manager_is_retired' => $this->manager->isRetired(),
            ]
        );
    }

    /**
     * Suspend a manager.
     */
    public function suspend(): void
    {
        Gate::authorize('suspend', $this->manager);

        $this->executeActionWithContext(
            'suspended',
            SuspendAction::class,
            $this->manager,
            'manager',
            fn () => [
                'manager_current_status' => $this->manager->status,
                'manager_is_employed' => $this->manager->isEmployed(),
                'manager_is_injured' => $this->manager->isInjured(),
            ]
        );
    }

    /**
     * Reinstate a manager.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->manager);

        $this->executeActionWithContext(
            'reinstated',
            ReinstateAction::class,
            $this->manager,
            'manager',
            fn () => [
                'manager_current_status' => $this->manager->status,
                'manager_is_suspended' => $this->manager->isSuspended(),
                'manager_is_injured' => $this->manager->isInjured(),
            ]
        );
    }

    /**
     * Injure a manager.
     */
    public function injure(): void
    {
        Gate::authorize('injure', $this->manager);

        $this->executeActionWithContext(
            'injured',
            InjureAction::class,
            $this->manager,
            'manager',
            fn () => [
                'manager_current_status' => $this->manager->status,
                'manager_is_employed' => $this->manager->isEmployed(),
                'manager_is_suspended' => $this->manager->isSuspended(),
            ]
        );
    }

    /**
     * Heal a manager from injury.
     */
    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->manager);

        $this->executeActionWithContext(
            'healed',
            HealAction::class,
            $this->manager,
            'manager',
            fn () => [
                'manager_current_status' => $this->manager->status,
                'manager_is_injured' => $this->manager->isInjured(),
            ]
        );
    }

    /**
     * Restore a deleted manager.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->manager);

        $this->executeActionWithContext(
            'restored',
            RestoreAction::class,
            $this->manager,
            'manager',
            fn () => [
                'manager_is_deleted' => ! is_null($this->manager->deleted_at),
            ]
        );
    }

    public function render(): View
    {
        return view('livewire.managers.components.actions');
    }
}
