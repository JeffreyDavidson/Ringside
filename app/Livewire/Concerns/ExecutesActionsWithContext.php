<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Services\ErrorMessageMappingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Trait for executing business actions with rich context and error handling.
 *
 * This trait provides a standardized way to execute business actions in Livewire
 * components with comprehensive context tracking, structured error handling, and
 * user-friendly error messages. It eliminates code duplication across entity
 * action components (Wrestlers, Managers, Referees, etc.).
 */
trait ExecutesActionsWithContext
{
    /**
     * Execute a business action with comprehensive context and error handling.
     *
     * @param  string  $actionName  Human-readable action name (e.g., 'employ', 'release')
     * @param  string  $actionClass  Fully qualified action class name
     * @param  Model  $entity  The entity being acted upon
     * @param  string  $entityType  Entity type for context (e.g., 'wrestler', 'manager')
     * @param  callable  $contextProvider  Function that returns entity-specific context data
     * @param  array  $actionParams  Additional parameters to pass to the action
     */
    protected function executeActionWithContext(
        string $actionName,
        string $actionClass,
        Model $entity,
        string $entityType,
        callable $contextProvider,
        array $actionParams = []
    ): void {
        // Set up comprehensive context for debugging
        Context::add('action', "{$entityType}_{$actionName}");
        Context::add("{$entityType}_id", $entity->getKey());

        // Add entity-specific context
        foreach ($contextProvider() as $key => $value) {
            Context::add($key, $value);
        }

        Context::push('action_breadcrumbs', "{$actionName}_action_started");

        try {
            Context::push('action_breadcrumbs', 'authorization_passed');

            // Execute the action
            $action = resolve($actionClass);
            if (empty($actionParams)) {
                $action->handle($entity);
            } else {
                $action->handle($entity, ...$actionParams);
            }

            Context::push('action_breadcrumbs', 'action_completed_successfully');

            // Success handling
            $this->dispatch("{$entityType}-updated");
            session()->flash('success', __("{$entityType}s.actions.{$actionName}"));

        } catch (Throwable $e) {
            Context::push('action_breadcrumbs', 'action_failed_with_exception');

            // Log technical details for developers
            Log::warning(ucfirst($entityType)." {$actionName} failed", [
                'exception_type' => get_class($e),
                'business_rule_violation' => $e->getMessage(),
                "{$entityType}_data" => $this->buildEntityLogData($entity, $entityType),
            ]);

            // Show user-friendly message
            $userMessage = $this->mapExceptionToUserMessage($e, $entityType);
            session()->flash('error', __($userMessage));
        }
    }

    /**
     * Build standardized entity data for logging.
     *
     * @param  Model  $entity  The entity being logged
     * @param  string  $entityType  Entity type for context
     */
    protected function buildEntityLogData(Model $entity, string $entityType): array
    {
        $baseData = [
            'id' => $entity->getKey(),
            'status' => $entity->status ?? null,
        ];

        // Add entity-specific fields
        return match ($entityType) {
            'wrestler' => array_merge($baseData, [
                'name' => $entity->name,
                'is_employed' => $entity->isEmployed(),
                'is_suspended' => $entity->isSuspended(),
                'is_retired' => $entity->isRetired(),
                'is_injured' => $entity->isInjured(),
            ]),
            'manager' => array_merge($baseData, [
                'first_name' => $entity->first_name,
                'last_name' => $entity->last_name,
                'is_employed' => $entity->isEmployed(),
                'is_suspended' => $entity->isSuspended(),
                'is_retired' => $entity->isRetired(),
                'is_injured' => $entity->isInjured(),
            ]),
            'referee' => array_merge($baseData, [
                'first_name' => $entity->first_name,
                'last_name' => $entity->last_name,
                'is_employed' => $entity->isEmployed(),
                'is_suspended' => $entity->isSuspended(),
                'is_retired' => $entity->isRetired(),
                'is_injured' => $entity->isInjured(),
            ]),
            'tag-team' => array_merge($baseData, [
                'name' => $entity->name,
                'signature_move' => $entity->signature_move,
                'is_employed' => $entity->isEmployed(),
                'is_suspended' => $entity->isSuspended(),
                'is_retired' => $entity->isRetired(),
            ]),
            default => $baseData,
        };
    }

    /**
     * Map exception to user-friendly message based on entity type.
     *
     * @param  Throwable  $exception  The exception to map
     * @param  string  $entityType  Entity type for mapping
     */
    protected function mapExceptionToUserMessage(Throwable $exception, string $entityType): string
    {
        return match ($entityType) {
            'wrestler' => ErrorMessageMappingService::mapWrestlerException($exception),
            'manager' => ErrorMessageMappingService::mapManagerException($exception),
            'referee' => ErrorMessageMappingService::mapRefereeException($exception),
            'tag-team' => ErrorMessageMappingService::mapTagTeamException($exception),
            default => "{$entityType}s.errors.general_error",
        };
    }
}
