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
use App\Models\Managers\Manager;
use App\Services\ErrorMessageMappingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

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

        // Add rich context for debugging
        Context::add('action', 'manager_employ');
        Context::add('manager_current_status', $this->manager->status);
        Context::add('manager_is_employed', $this->manager->isEmployed());
        Context::add('manager_is_suspended', $this->manager->isSuspended());
        Context::add('manager_is_retired', $this->manager->isRetired());
        Context::add('manager_is_injured', $this->manager->isInjured());
        Context::push('action_breadcrumbs', 'employ_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(EmployAction::class)->handle($this->manager);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('manager-updated');
            session()->flash('success', __('managers.actions.employed'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            // Log technical details for developers
            Log::warning('Manager employment failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'manager_data' => [
                    'id' => $this->manager->id,
                    'first_name' => $this->manager->first_name,
                    'last_name' => $this->manager->last_name,
                    'status' => $this->manager->status,
                    'is_employed' => $this->manager->isEmployed(),
                    'is_suspended' => $this->manager->isSuspended(),
                    'is_retired' => $this->manager->isRetired(),
                    'is_injured' => $this->manager->isInjured(),
                ],
            ]);

            // Show user-friendly message
            $userMessage = ErrorMessageMappingService::mapManagerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Release a manager.
     */
    public function release(): void
    {
        Gate::authorize('release', $this->manager);

        Context::add('action', 'manager_release');
        Context::add('manager_current_status', $this->manager->status);
        Context::add('manager_is_employed', $this->manager->isEmployed());
        Context::add('manager_is_suspended', $this->manager->isSuspended());
        Context::push('action_breadcrumbs', 'release_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(ReleaseAction::class)->handle($this->manager);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('manager-updated');
            session()->flash('success', __('managers.actions.released'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Manager release failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'manager_data' => [
                    'id' => $this->manager->id,
                    'first_name' => $this->manager->first_name,
                    'last_name' => $this->manager->last_name,
                    'status' => $this->manager->status,
                    'is_employed' => $this->manager->isEmployed(),
                    'is_suspended' => $this->manager->isSuspended(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapManagerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Retire a manager.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->manager);

        Context::add('action', 'manager_retire');
        Context::add('manager_current_status', $this->manager->status);
        Context::add('manager_is_employed', $this->manager->isEmployed());
        Context::add('manager_is_suspended', $this->manager->isSuspended());
        Context::push('action_breadcrumbs', 'retire_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(RetireAction::class)->handle($this->manager);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('manager-updated');
            session()->flash('success', __('managers.actions.retired'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Manager retirement failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'manager_data' => [
                    'id' => $this->manager->id,
                    'first_name' => $this->manager->first_name,
                    'last_name' => $this->manager->last_name,
                    'status' => $this->manager->status,
                    'is_employed' => $this->manager->isEmployed(),
                    'is_suspended' => $this->manager->isSuspended(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapManagerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Unretire a manager.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->manager);

        Context::add('action', 'manager_unretire');
        Context::add('manager_current_status', $this->manager->status);
        Context::add('manager_is_retired', $this->manager->isRetired());
        Context::push('action_breadcrumbs', 'unretire_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(UnretireAction::class)->handle($this->manager);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('manager-updated');
            session()->flash('success', __('managers.actions.unretired'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Manager unretirement failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'manager_data' => [
                    'id' => $this->manager->id,
                    'first_name' => $this->manager->first_name,
                    'last_name' => $this->manager->last_name,
                    'status' => $this->manager->status,
                    'is_retired' => $this->manager->isRetired(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapManagerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Suspend a manager.
     */
    public function suspend(): void
    {
        Gate::authorize('suspend', $this->manager);

        Context::add('action', 'manager_suspend');
        Context::add('manager_current_status', $this->manager->status);
        Context::add('manager_is_employed', $this->manager->isEmployed());
        Context::add('manager_is_injured', $this->manager->isInjured());
        Context::push('action_breadcrumbs', 'suspend_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(SuspendAction::class)->handle($this->manager);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('manager-updated');
            session()->flash('success', __('managers.actions.suspended'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Manager suspension failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'manager_data' => [
                    'id' => $this->manager->id,
                    'first_name' => $this->manager->first_name,
                    'last_name' => $this->manager->last_name,
                    'status' => $this->manager->status,
                    'is_employed' => $this->manager->isEmployed(),
                    'is_injured' => $this->manager->isInjured(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapManagerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Reinstate a manager.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->manager);

        Context::add('action', 'manager_reinstate');
        Context::add('manager_current_status', $this->manager->status);
        Context::add('manager_is_suspended', $this->manager->isSuspended());
        Context::add('manager_is_injured', $this->manager->isInjured());
        Context::push('action_breadcrumbs', 'reinstate_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(ReinstateAction::class)->handle($this->manager);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('manager-updated');
            session()->flash('success', __('managers.actions.reinstated'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Manager reinstatement failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'manager_data' => [
                    'id' => $this->manager->id,
                    'first_name' => $this->manager->first_name,
                    'last_name' => $this->manager->last_name,
                    'status' => $this->manager->status,
                    'is_suspended' => $this->manager->isSuspended(),
                    'is_injured' => $this->manager->isInjured(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapManagerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Injure a manager.
     */
    public function injure(): void
    {
        Gate::authorize('injure', $this->manager);

        Context::add('action', 'manager_injure');
        Context::add('manager_current_status', $this->manager->status);
        Context::add('manager_is_employed', $this->manager->isEmployed());
        Context::add('manager_is_suspended', $this->manager->isSuspended());
        Context::push('action_breadcrumbs', 'injure_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(InjureAction::class)->handle($this->manager);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('manager-updated');
            session()->flash('success', __('managers.actions.injured'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Manager injury recording failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'manager_data' => [
                    'id' => $this->manager->id,
                    'first_name' => $this->manager->first_name,
                    'last_name' => $this->manager->last_name,
                    'status' => $this->manager->status,
                    'is_employed' => $this->manager->isEmployed(),
                    'is_suspended' => $this->manager->isSuspended(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapManagerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Heal a manager from injury.
     */
    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->manager);

        Context::add('action', 'manager_heal');
        Context::add('manager_current_status', $this->manager->status);
        Context::add('manager_is_injured', $this->manager->isInjured());
        Context::push('action_breadcrumbs', 'heal_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(HealAction::class)->handle($this->manager);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('manager-updated');
            session()->flash('success', __('managers.actions.healed'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Manager injury healing failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'manager_data' => [
                    'id' => $this->manager->id,
                    'first_name' => $this->manager->first_name,
                    'last_name' => $this->manager->last_name,
                    'status' => $this->manager->status,
                    'is_injured' => $this->manager->isInjured(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapManagerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Restore a deleted manager.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->manager);

        Context::add('action', 'manager_restore');
        Context::add('manager_is_deleted', ! is_null($this->manager->deleted_at));
        Context::push('action_breadcrumbs', 'restore_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(RestoreAction::class)->handle($this->manager);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('manager-updated');
            session()->flash('success', __('managers.actions.restored'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Manager restoration failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'manager_data' => [
                    'id' => $this->manager->id,
                    'first_name' => $this->manager->first_name,
                    'last_name' => $this->manager->last_name,
                    'deleted_at' => $this->manager->deleted_at,
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapManagerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    public function render(): View
    {
        return view('livewire.managers.components.actions');
    }
}
