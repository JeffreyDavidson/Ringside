<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use LivewireUI\Modal\ModalComponent;

/**
 * Base class for all modal components.
 *
 * This abstract class provides the foundation for all modal dialog components
 * in the application. It handles the common modal lifecycle, form integration,
 * and provides a consistent interface for modal operations across different
 * entity types.
 *
 * The class is designed to work seamlessly with BaseForm implementations,
 * providing a standardized way to display forms within modal dialogs while
 * maintaining proper state management and user experience patterns.
 *
 * Key Features:
 * - Seamless integration with BaseForm classes
 * - Automatic modal title generation based on operation type
 * - Proper model binding and form state management
 * - Event dispatching for component communication
 * - Consistent modal lifecycle management
 * - Type safety through generics
 *
 * @template TModelForm of BaseForm
 * @template TModelType of Model
 *
 * @author Your Name
 * @since 1.0.0
 * @see BaseForm For form integration requirements
 * @see BaseFormModal For form-specific modal implementation
 */
abstract class BaseModal extends ModalComponent
{
    protected ?Model $model;

    /**
     * @var TModelForm
     */
    protected $modelForm;

    /**
     * @var TModelType
     */
    protected Model $modelType;

    protected string $modalFormPath;

    protected string $modelTitleField;

    protected string $titleField;

    /**
     * Initialize the modal with proper configuration and lifecycle setup.
     *
     * This method is called automatically by Livewire when the modal component
     * is instantiated. It handles the initial setup including model loading,
     * form binding, and state preparation for both create and edit operations.
     *
     * @param int|string|null $modelId The ID of the model to edit, or null for creation mode
     */
    public function mount(int|string|null $modelId = null): void
    {
        if (isset($modelId)) {
            try {
                $id = is_numeric($modelId) ? (int) $modelId : $modelId;
                $this->model = $this->modelType::findOrFail($id);
                $this->modelForm->setModel($this->model);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    /**
     * Generate the modal title based on the current operation.
     *
     * Creates user-friendly modal titles that indicate whether the user is
     * creating a new record or editing an existing one. For edit operations,
     * it attempts to include the model's display name for better context.
     *
     * @return string The generated modal title
     */
    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit '.$this->modelForm->generateModelEditName($this->modelTitleField);
        }

        return 'Add '.(isset($this->modelType) ? class_basename($this->modelType) : 'Record');
    }

    /**
     * Clear the form and reset it to the appropriate state.
     *
     * Resets the form to its initial state, either with the bound model data
     * (for edit operations) or to empty state (for create operations).
     */
    public function clear(): void
    {
        if (! is_null($this->model)) {
            $this->modelForm->setModel($this->model);
        } else {
            $this->modelForm->reset();
        }
    }

    /**
     * Save the form data and handle the modal lifecycle.
     *
     * Processes the form submission, and if successful, dispatches events
     * to refresh related components and closes the modal. This provides
     * a consistent save workflow across all modal implementations.
     */
    public function save(): void
    {
        if ($this->modelForm->store()) {
            $this->dispatch('refreshDatatable');

            $this->closeModal();
        }
    }

    /**
     * Render the modal view.
     *
     * Returns the appropriate view for the modal, with fallback handling
     * for missing views to prevent errors during development.
     *
     * @return View The rendered modal view
     */
    public function render(): View
    {
        // Ensure modalFormPath is set
        if (! isset($this->modalFormPath)) {
            $this->modalFormPath = 'blank';
        }

        $view = 'livewire.'.$this->modalFormPath;

        if (! view()->exists($view)) {
            $view = 'blank';
        }

        return view($view);
    }
}