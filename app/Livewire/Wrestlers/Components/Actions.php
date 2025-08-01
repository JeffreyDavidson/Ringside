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
use App\Models\Wrestlers\Wrestler;
use App\Services\ErrorMessageMappingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

/**
 * Wrestler Actions Component
 *
 * Handles all business actions that can be performed on a wrestler including
 * employment management, health status changes, and career lifecycle operations.
 * This component is designed to be reusable across different contexts (tables,
 * detail pages, cards, etc.) while maintaining consistent authorization and
 * error handling patterns.
 */
class Actions extends Component
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

        // Add rich context for debugging
        Context::add('action', 'wrestler_employ');
        Context::add('wrestler_current_status', $this->wrestler->status);
        Context::add('wrestler_is_employed', $this->wrestler->isEmployed());
        Context::add('wrestler_is_suspended', $this->wrestler->isSuspended());
        Context::add('wrestler_is_retired', $this->wrestler->isRetired());
        Context::push('action_breadcrumbs', 'employ_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(EmployAction::class)->handle($this->wrestler);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('wrestler-updated');
            session()->flash('success', __('wrestlers.actions.employed'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            // Log technical details for developers
            Log::warning('Wrestler employment failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'wrestler_data' => [
                    'id' => $this->wrestler->id,
                    'name' => $this->wrestler->name,
                    'current_employment_count' => $this->wrestler->employments()->count(),
                    'current_managers_count' => $this->wrestler->currentManagers->count(),
                ],
            ]);

            // Show user-friendly message
            $userMessage = ErrorMessageMappingService::mapWrestlerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Release a wrestler.
     */
    public function release(): void
    {
        Gate::authorize('release', $this->wrestler);

        Context::add('action', 'wrestler_release');
        Context::add('wrestler_employment_status', $this->wrestler->isEmployed());
        Context::push('action_breadcrumbs', 'release_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(ReleaseAction::class)->handle($this->wrestler);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('wrestler-updated');
            session()->flash('success', __('wrestlers.actions.released'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Wrestler release failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'wrestler_data' => [
                    'id' => $this->wrestler->id,
                    'name' => $this->wrestler->name,
                    'is_employed' => $this->wrestler->isEmployed(),
                    'current_employment' => $this->wrestler->currentEmployment?->toArray(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapWrestlerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Retire a wrestler.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->wrestler);

        Context::add('action', 'wrestler_retire');
        Context::add('wrestler_employment_status', $this->wrestler->isEmployed());
        Context::add('wrestler_retirement_status', $this->wrestler->isRetired());
        Context::push('action_breadcrumbs', 'retire_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(RetireAction::class)->handle($this->wrestler);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('wrestler-updated');
            session()->flash('success', __('wrestlers.actions.retired'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Wrestler retirement failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'wrestler_data' => [
                    'id' => $this->wrestler->id,
                    'name' => $this->wrestler->name,
                    'is_employed' => $this->wrestler->isEmployed(),
                    'is_retired' => $this->wrestler->isRetired(),
                    'retirement_count' => $this->wrestler->retirements()->count(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapWrestlerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Unretire a wrestler.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->wrestler);

        Context::add('action', 'wrestler_unretire');
        Context::add('wrestler_retirement_status', $this->wrestler->isRetired());
        Context::push('action_breadcrumbs', 'unretire_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(UnretireAction::class)->handle($this->wrestler);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('wrestler-updated');
            session()->flash('success', __('wrestlers.actions.unretired'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Wrestler unretirement failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'wrestler_data' => [
                    'id' => $this->wrestler->id,
                    'name' => $this->wrestler->name,
                    'is_retired' => $this->wrestler->isRetired(),
                    'current_retirement' => $this->wrestler->currentRetirement?->toArray(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapWrestlerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Suspend a wrestler.
     */
    public function suspend(): void
    {
        Gate::authorize('suspend', $this->wrestler);

        Context::add('action', 'wrestler_suspend');
        Context::add('wrestler_suspension_status', $this->wrestler->isSuspended());
        Context::push('action_breadcrumbs', 'suspend_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(SuspendAction::class)->handle($this->wrestler);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('wrestler-updated');
            session()->flash('success', __('wrestlers.actions.suspended'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Wrestler suspension failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'wrestler_data' => [
                    'id' => $this->wrestler->id,
                    'name' => $this->wrestler->name,
                    'is_suspended' => $this->wrestler->isSuspended(),
                    'suspension_count' => $this->wrestler->suspensions()->count(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapWrestlerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Reinstate a wrestler.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->wrestler);

        Context::add('action', 'wrestler_reinstate');
        Context::add('wrestler_suspension_status', $this->wrestler->isSuspended());
        Context::add('wrestler_injury_status', $this->wrestler->isInjured());
        Context::push('action_breadcrumbs', 'reinstate_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(ReinstateAction::class)->handle($this->wrestler);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('wrestler-updated');
            session()->flash('success', __('wrestlers.actions.reinstated'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Wrestler reinstatement failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'wrestler_data' => [
                    'id' => $this->wrestler->id,
                    'name' => $this->wrestler->name,
                    'is_suspended' => $this->wrestler->isSuspended(),
                    'is_injured' => $this->wrestler->isInjured(),
                    'current_suspension' => $this->wrestler->currentSuspension?->toArray(),
                    'current_injury' => $this->wrestler->currentInjury()?->toArray(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapWrestlerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Injure a wrestler.
     */
    public function injure(): void
    {
        Gate::authorize('injure', $this->wrestler);

        Context::add('action', 'wrestler_injure');
        Context::add('wrestler_injury_status', $this->wrestler->isInjured());
        Context::push('action_breadcrumbs', 'injure_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(InjureAction::class)->handle($this->wrestler);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('wrestler-updated');
            session()->flash('success', __('wrestlers.actions.injured'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Wrestler injury recording failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'wrestler_data' => [
                    'id' => $this->wrestler->id,
                    'name' => $this->wrestler->name,
                    'is_injured' => $this->wrestler->isInjured(),
                    'injury_count' => $this->wrestler->injuries()->count(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapWrestlerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Heal a wrestler from injury.
     */
    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->wrestler);

        Context::add('action', 'wrestler_heal');
        Context::add('wrestler_injury_status', $this->wrestler->isInjured());
        Context::push('action_breadcrumbs', 'heal_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(HealAction::class)->handle($this->wrestler);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('wrestler-updated');
            session()->flash('success', __('wrestlers.actions.healed'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Wrestler healing failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'wrestler_data' => [
                    'id' => $this->wrestler->id,
                    'name' => $this->wrestler->name,
                    'is_injured' => $this->wrestler->isInjured(),
                    'current_injury' => $this->wrestler->currentInjury()?->toArray(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapWrestlerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Restore a deleted wrestler.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->wrestler);

        Context::add('action', 'wrestler_restore');
        Context::add('wrestler_deleted_status', $this->wrestler->trashed());
        Context::push('action_breadcrumbs', 'restore_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(RestoreAction::class)->handle($this->wrestler);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('wrestler-updated');
            session()->flash('success', __('wrestlers.actions.restored'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Wrestler restoration failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'wrestler_data' => [
                    'id' => $this->wrestler->id,
                    'name' => $this->wrestler->name,
                    'is_trashed' => $this->wrestler->trashed(),
                    'deleted_at' => $this->wrestler->deleted_at?->toISOString(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapWrestlerException($e);
            session()->flash('error', __($userMessage));
        }
    }

    public function render(): View
    {
        return view('livewire.wrestlers.components.actions');
    }
}
