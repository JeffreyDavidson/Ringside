<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Base class for all Actions components.
 *
 * This abstract class provides the foundation for all action components
 * in the application. It standardizes the pattern for business action
 * components that handle entity state changes, authorization, error handling,
 * and event dispatching.
 *
 * The class establishes common patterns for:
 * - Authorization checking before action execution
 * - Consistent error handling and user feedback
 * - Event dispatching for component communication
 * - Action result handling and flash messaging
 *
 * Key Features:
 * - Standardized authorization pattern using Laravel Gates
 * - Consistent error handling with try-catch blocks
 * - Event dispatching for real-time UI updates
 * - Flash messaging for user feedback
 * - Template methods for action execution
 * - Type safety through generics
 *
 * Design Principles:
 * - Fail fast: Authorization is checked before action execution
 * - Consistent feedback: Success and error messages follow standard patterns
 * - Event-driven: Actions dispatch events for component communication
 * - Single responsibility: Each action method handles one business operation
 *
 * @template TModel of Model The Eloquent model type this component manages
 *
 * @author Your Name
 * @since 1.0.0
 * @see Actions For concrete implementation examples
 *
 * @example
 * ```php
 * // Creating a concrete actions component
 * class Actions extends BaseActions
 * {
 *     public Wrestler $wrestler;
 *
 *     public function mount(Wrestler $wrestler): void
 *     {
 *         $this->wrestler = $wrestler;
 *     }
 *
 *     public function employ(): void
 *     {
 *         $this->executeAction(
 *             'employ',
 *             $this->wrestler,
 *             EmployAction::class,
 *             'Wrestler successfully employed.',
 *             'wrestler-updated'
 *         );
 *     }
 *
 *     public function release(): void
 *     {
 *         $this->executeAction(
 *             'release',
 *             $this->wrestler,
 *             ReleaseAction::class,
 *             'Wrestler successfully released.',
 *             'wrestler-updated'
 *         );
 *     }
 * }
 * ```
 */
abstract class BaseActions extends Component
{
    /**
     * Execute a business action with standardized error handling and feedback.
     *
     * This method provides a consistent pattern for executing business actions
     * with proper authorization, error handling, and user feedback. It follows
     * the template method pattern to standardize action execution while allowing
     * flexibility in the specific actions and models being handled.
     *
     * The method handles:
     * 1. Authorization checking using Laravel Gates
     * 2. Action execution with dependency injection
     * 3. Event dispatching for component communication
     * 4. Success and error message handling
     * 5. Exception catching and user-friendly error display
     *
     * @param string $ability The Gate ability to check for authorization
     * @param Model $model The model instance to perform the action on
     * @param string $actionClass The fully qualified action class name
     * @param string $successMessage The message to display on successful action
     * @param string $eventName The event name to dispatch on success
     * @param mixed ...$actionParams Additional parameters to pass to the action
     *
     * @example
     * ```php
     * // In a concrete actions component
     * public function employ(): void
     * {
     *     $this->executeAction(
     *         'employ',
     *         $this->wrestler,
     *         EmployAction::class,
     *         'Wrestler successfully employed.',
     *         'wrestler-updated'
     *     );
     * }
     *
     * // With additional parameters
     * public function suspend(): void
     * {
     *     $this->executeAction(
     *         'suspend',
     *         $this->wrestler,
     *         SuspendAction::class,
     *         'Wrestler successfully suspended.',
     *         'wrestler-updated',
     *         $this->suspensionReason,
     *         $this->suspensionDate
     *     );
     * }
     * ```
     */
    protected function executeAction(
        string $ability,
        Model $model,
        string $actionClass,
        string $successMessage,
        string $eventName,
        mixed ...$actionParams
    ): void {
        Gate::authorize($ability, $model);

        try {
            $action = resolve($actionClass);
            $action->handle($model, ...$actionParams);
            
            $this->dispatch($eventName);
            session()->flash('status', $successMessage);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Get the model instance that this actions component manages.
     *
     * This method should return the model instance that the actions component
     * is operating on. It provides a standardized way to access the model
     * across different action methods.
     *
     * @return TModel The model instance being managed
     */
    abstract protected function getModel(): Model;

    /**
     * Get the base event name for this actions component.
     *
     * This method should return the base event name that will be used for
     * dispatching events when actions are executed. This allows for consistent
     * event naming across all actions in the component.
     *
     * @return string The base event name (e.g., 'wrestler-updated', 'manager-updated')
     */
    abstract protected function getBaseEventName(): string;
}