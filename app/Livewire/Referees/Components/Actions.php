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
use App\Models\Referees\Referee;
use App\Services\ErrorMessageMappingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

/**
 * Referee Actions Component
 *
 * Handles all business actions that can be performed on a referee including
 * employment management, health status changes, and career lifecycle operations.
 * This component is designed to be reusable across different contexts (tables,
 * detail pages, cards, etc.) while maintaining consistent authorization and
 * error handling patterns.
 */
class Actions extends Component
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

        // Add rich context for debugging
        Context::add('action', 'referee_employ');
        Context::add('referee_current_status', $this->referee->status);
        Context::add('referee_is_employed', $this->referee->isEmployed());
        Context::add('referee_is_suspended', $this->referee->isSuspended());
        Context::add('referee_is_retired', $this->referee->isRetired());
        Context::push('action_breadcrumbs', 'employ_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(EmployAction::class)->handle($this->referee);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('referee-updated');
            session()->flash('success', __('referees.actions.employed'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            // Log technical details for developers
            Log::warning('Referee employment failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'referee_data' => [
                    'id' => $this->referee->id,
                    'first_name' => $this->referee->first_name,
                    'last_name' => $this->referee->last_name,
                    'current_employment_count' => $this->referee->employments()->count(),
                ],
            ]);

            // Show user-friendly message
            $userMessage = ErrorMessageMappingService::mapRefereeException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Release a referee.
     */
    public function release(): void
    {
        Gate::authorize('release', $this->referee);

        Context::add('action', 'referee_release');
        Context::add('referee_employment_status', $this->referee->isEmployed());
        Context::push('action_breadcrumbs', 'release_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(ReleaseAction::class)->handle($this->referee);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('referee-updated');
            session()->flash('success', __('referees.actions.released'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Referee release failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'referee_data' => [
                    'id' => $this->referee->id,
                    'first_name' => $this->referee->first_name,
                    'last_name' => $this->referee->last_name,
                    'is_employed' => $this->referee->isEmployed(),
                    'current_employment' => $this->referee->currentEmployment?->toArray(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapRefereeException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Retire a referee.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->referee);

        Context::add('action', 'referee_retire');
        Context::add('referee_employment_status', $this->referee->isEmployed());
        Context::add('referee_retirement_status', $this->referee->isRetired());
        Context::push('action_breadcrumbs', 'retire_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(RetireAction::class)->handle($this->referee);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('referee-updated');
            session()->flash('success', __('referees.actions.retired'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Referee retirement failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'referee_data' => [
                    'id' => $this->referee->id,
                    'first_name' => $this->referee->first_name,
                    'last_name' => $this->referee->last_name,
                    'is_employed' => $this->referee->isEmployed(),
                    'is_retired' => $this->referee->isRetired(),
                    'retirement_count' => $this->referee->retirements()->count(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapRefereeException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Unretire a referee.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->referee);

        Context::add('action', 'referee_unretire');
        Context::add('referee_retirement_status', $this->referee->isRetired());
        Context::push('action_breadcrumbs', 'unretire_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(UnretireAction::class)->handle($this->referee);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('referee-updated');
            session()->flash('success', __('referees.actions.unretired'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Referee unretirement failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'referee_data' => [
                    'id' => $this->referee->id,
                    'first_name' => $this->referee->first_name,
                    'last_name' => $this->referee->last_name,
                    'is_retired' => $this->referee->isRetired(),
                    'current_retirement' => $this->referee->currentRetirement?->toArray(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapRefereeException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Suspend a referee.
     */
    public function suspend(): void
    {
        Gate::authorize('suspend', $this->referee);

        Context::add('action', 'referee_suspend');
        Context::add('referee_suspension_status', $this->referee->isSuspended());
        Context::push('action_breadcrumbs', 'suspend_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(SuspendAction::class)->handle($this->referee);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('referee-updated');
            session()->flash('success', __('referees.actions.suspended'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Referee suspension failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'referee_data' => [
                    'id' => $this->referee->id,
                    'first_name' => $this->referee->first_name,
                    'last_name' => $this->referee->last_name,
                    'is_suspended' => $this->referee->isSuspended(),
                    'suspension_count' => $this->referee->suspensions()->count(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapRefereeException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Reinstate a referee.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->referee);

        Context::add('action', 'referee_reinstate');
        Context::add('referee_suspension_status', $this->referee->isSuspended());
        Context::push('action_breadcrumbs', 'reinstate_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(ReinstateAction::class)->handle($this->referee);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('referee-updated');
            session()->flash('success', __('referees.actions.reinstated'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Referee reinstatement failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'referee_data' => [
                    'id' => $this->referee->id,
                    'first_name' => $this->referee->first_name,
                    'last_name' => $this->referee->last_name,
                    'is_suspended' => $this->referee->isSuspended(),
                    'current_suspension' => $this->referee->currentSuspension?->toArray(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapRefereeException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Injure a referee.
     */
    public function injure(): void
    {
        Gate::authorize('injure', $this->referee);

        Context::add('action', 'referee_injure');
        Context::add('referee_injury_status', $this->referee->isInjured());
        Context::push('action_breadcrumbs', 'injure_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(InjureAction::class)->handle($this->referee);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('referee-updated');
            session()->flash('success', __('referees.actions.injured'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Referee injury recording failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'referee_data' => [
                    'id' => $this->referee->id,
                    'first_name' => $this->referee->first_name,
                    'last_name' => $this->referee->last_name,
                    'is_injured' => $this->referee->isInjured(),
                    'injury_count' => $this->referee->injuries()->count(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapRefereeException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Heal a referee from injury.
     */
    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->referee);

        Context::add('action', 'referee_heal');
        Context::add('referee_injury_status', $this->referee->isInjured());
        Context::push('action_breadcrumbs', 'heal_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(HealAction::class)->handle($this->referee);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('referee-updated');
            session()->flash('success', __('referees.actions.healed'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Referee healing failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'referee_data' => [
                    'id' => $this->referee->id,
                    'first_name' => $this->referee->first_name,
                    'last_name' => $this->referee->last_name,
                    'is_injured' => $this->referee->isInjured(),
                    'current_injury' => $this->referee->currentInjury?->toArray(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapRefereeException($e);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Restore a deleted referee.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->referee);

        Context::add('action', 'referee_restore');
        Context::add('referee_deleted_status', $this->referee->trashed());
        Context::push('action_breadcrumbs', 'restore_action_started');

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');
            resolve(RestoreAction::class)->handle($this->referee);
            Context::push('action_breadcrumbs', 'action_completed_successfully');

            $this->dispatch('referee-updated');
            session()->flash('success', __('referees.actions.restored'));
        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            Log::warning('Referee restoration failed', [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                'referee_data' => [
                    'id' => $this->referee->id,
                    'first_name' => $this->referee->first_name,
                    'last_name' => $this->referee->last_name,
                    'is_trashed' => $this->referee->trashed(),
                    'deleted_at' => $this->referee->deleted_at?->toISOString(),
                ],
            ]);

            $userMessage = ErrorMessageMappingService::mapRefereeException($e);
            session()->flash('error', __($userMessage));
        }
    }

    public function render(): View
    {
        return view('livewire.referees.components.actions');
    }
}
