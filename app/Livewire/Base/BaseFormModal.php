<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Concerns\GeneratesDummyData;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all form modals with essential functionality.
 *
 * This base class provides a standardized foundation for creating modal dialogs
 * that contain forms for creating or editing Eloquent models. It combines the
 * modal functionality from BaseModal with form-specific features like dummy
 * data generation for development and testing purposes.
 *
 * The class follows a simple template method pattern where child classes provide
 * configuration details (form class, model class, modal path) while this base
 * class handles the common modal lifecycle and integration with the dummy data
 * generation system.
 *
 * Key Features:
 * - Seamless integration with BaseModal for consistent modal behavior
 * - Automatic dummy data generation via GeneratesDummyData trait
 * - Type-safe form and model class specification
 * - Simplified configuration through abstract methods
 * - Proper Livewire component lifecycle management
 *
 * Design Philosophy:
 * - Composition over complexity: Uses traits for specialized functionality
 * - Configuration over convention: Child classes specify their requirements
 * - Fail fast: Let individual components handle their own validation
 * - Single responsibility: Focus on modal-specific concerns
 *
 * @template TForm of LivewireBaseForm The form class this modal manages
 * @template TModel of Model The Eloquent model type this modal creates/edits
 *
 * @extends BaseModal<TForm, TModel>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseModal For core modal functionality and lifecycle management
 * @see GeneratesDummyData For dummy data generation capabilities
 * @see LivewireBaseForm For form implementation requirements
 *
 * @example
 * ```php
 * // Creating a wrestler form modal
 * class WrestlerFormModal extends BaseFormModal
 * {
 *     protected function getFormClass(): string
 *     {
 *         return WrestlerForm::class;
 *     }
 *
 *     protected function getModelClass(): string
 *     {
 *         return Wrestler::class;
 *     }
 *
 *     protected function getModalPath(): string
 *     {
 *         return 'modals.wrestler-form';
 *     }
 *
 *     protected function getDummyDataFields(): array
 *     {
 *         return [
 *             'name' => fn() => $this->generateWrestlingName(),
 *             'hometown' => fn() => fake()->city(),
 *             'weight' => fn() => fake()->numberBetween(150, 300),
 *             'signature_move' => fn() => $this->generateSignatureMove(),
 *         ];
 *     }
 * }
 *
 * // Usage in a Livewire component
 * class ManageWrestlers extends Component
 * {
 *     public WrestlerFormModal $modal;
 *
 *     public function createWrestler(): void
 *     {
 *         $this->modal->openModal();
 *     }
 *
 *     public function editWrestler(int $wrestlerId): void
 *     {
 *         $this->modal->openModal($wrestlerId);
 *     }
 * }
 *
 * // In Blade template
 * <button wire:click="createWrestler">Add Wrestler</button>
 * <button wire:click="modal.fillDummyFields">Fill Test Data</button>
 * ```
 */
abstract class BaseFormModal extends BaseModal
{
    use GeneratesDummyData;

    /**
     * Get the form class that this modal will instantiate and manage.
     *
     * Returns the fully qualified class name of the Livewire form that will
     * handle the creation or editing of models within this modal. The form
     * must extend LivewireBaseForm to ensure compatibility with the modal's
     * lifecycle and data binding patterns.
     *
     * This method is called during modal initialization to set up the proper
     * form instance for handling user input and model persistence.
     *
     * @return class-string<TForm> Fully qualified form class name
     *
     * @example
     * ```php
     * protected function getFormClass(): string
     * {
     *     return WrestlerForm::class;
     * }
     *
     * // For more complex scenarios:
     * protected function getFormClass(): string
     * {
     *     // Could return different forms based on context
     *     return $this->isAdvancedMode()
     *         ? AdvancedWrestlerForm::class
     *         : WrestlerForm::class;
     * }
     * ```
     *
     * @see LivewireBaseForm For form implementation requirements
     * @see mount() For when this method is called during initialization
     */
    abstract protected function getFormClass(): string;

    /**
     * Get the Eloquent model class that this modal creates or edits.
     *
     * Returns the fully qualified class name of the Eloquent model that this
     * modal's form will create (when opened without parameters) or edit (when
     * opened with a model ID). This class is used for type safety and proper
     * model resolution during the modal lifecycle.
     *
     * The model class must extend Illuminate\Database\Eloquent\Model and should
     * be compatible with the form class returned by getFormClass().
     *
     * @return class-string<TModel> Fully qualified model class name
     *
     * @example
     * ```php
     * protected function getModelClass(): string
     * {
     *     return Wrestler::class;
     * }
     *
     * // For polymorphic scenarios:
     * protected function getModelClass(): string
     * {
     *     return match($this->entityType) {
     *         'wrestler' => Wrestler::class,
     *         'manager' => Manager::class,
     *         default => throw new \InvalidArgumentException('Unknown entity type')
     *     };
     * }
     * ```
     *
     * @see Model For base model requirements
     * @see BaseModal For how model classes are used in modal lifecycle
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the Blade view path for rendering this modal.
     *
     * Returns the view path (relative to resources/views) that contains the
     * Blade template for this modal's UI. The view should include the modal
     * structure, form fields, and any modal-specific styling or JavaScript.
     *
     * The path follows Laravel's dot notation convention for nested directories
     * and should not include the .blade.php extension.
     *
     * @return string Blade view path using dot notation
     *
     * @example
     * ```php
     * protected function getModalPath(): string
     * {
     *     return 'modals.wrestler-form';
     *     // Corresponds to: resources/views/modals/wrestler-form.blade.php
     * }
     *
     * // For organized view structures:
     * protected function getModalPath(): string
     * {
     *     return 'livewire.modals.forms.wrestler';
     *     // Corresponds to: resources/views/livewire/modals/forms/wrestler.blade.php
     * }
     *
     * // For dynamic view selection:
     * protected function getModalPath(): string
     * {
     *     $baseView = 'modals.wrestler';
     *     return $this->isCompactMode() ? $baseView . '-compact' : $baseView . '-full';
     * }
     * ```
     *
     * @see mount() For when this path is set during modal initialization
     */
    abstract protected function getModalPath(): string;

    /**
     * Indicates if the modal is currently open.
     */
    public bool $isModalOpen = false;

    /**
     * Open the modal.
     */
    public function openModal(mixed $modelId = null): void
    {
        // Ensure proper initialization if mount wasn't called
        if (! isset($this->modalFormPath)) {
            $this->mount($modelId);
        } elseif ($modelId !== null) {
            // Re-mount with the specific model ID
            $this->mount($modelId);
        }

        $this->isModalOpen = true;
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->isModalOpen = false;
    }

    /**
     * Submit the form through the modal.
     *
     * This method provides a bridge for testing that allows calling
     * form submission directly on the modal component.
     */
    public function submitForm(): bool
    {
        // Ensure form has the correct model before submission
        // This handles cases where Livewire form property hydration
        // loses the model state between mount and submission
        if (isset($this->model) && $this->form) {
            $this->form->setModel($this->model);
        }

        $result = $this->form->store();

        if ($result) {
            $this->closeModal();
            $this->dispatch('form-submitted');
        }

        return $result;
    }

    /**
     * Initialize the modal with proper configuration and lifecycle setup.
     *
     * This method extends the parent BaseModal::mount() to add form-specific
     * initialization including setting the modal view path and ensuring proper
     * integration with the form and dummy data systems.
     *
     * The method is called automatically by Livewire when the component is
     * instantiated, whether for creating new records (modelId = null) or
     * editing existing ones (modelId provided).
     *
     * Initialization Flow:
     * 1. Set modal view path from getModalPath()
     * 2. Call parent mount() for core modal setup
     * 3. Form and model binding handled by parent class
     * 4. Dummy data capabilities available via trait
     *
     * @param  mixed  $modelId  The ID of the model to edit, or null for creation mode
     *
     * @example
     * ```php
     * // Called automatically by Livewire:
     * // - When opening modal for new record: mount(null)
     * // - When opening modal for editing: mount(123)
     *
     * // In parent component:
     * public function openCreateModal(): void
     * {
     *     $this->modal->mount(); // Triggers mount(null)
     * }
     *
     * public function openEditModal(int $id): void
     * {
     *     $this->modal->mount($id); // Triggers mount($id)
     * }
     * ```
     *
     * @see BaseModal::mount() For core modal initialization logic
     * @see getModalPath() For view path configuration
     */
    public function mount(mixed $modelId = null): void
    {
        $this->modalFormPath = $this->getModalPath();

        // Initialize the model type
        $modelClass = $this->getModelClass();
        $this->modelType = new $modelClass();

        // Use the existing form property (Livewire manages this automatically)
        // Don't create a new instance - use what Livewire provided

        // Set the form as the modelForm for BaseModal compatibility
        // IMPORTANT: Ensure both properties reference the same instance
        $this->modelForm = $this->form;

        // Set default title fields
        $this->modelTitleField = 'name';
        $this->titleField = 'name';

        parent::mount($modelId);

        // Ensure the form property also has the model if one was loaded
        // This is critical because Livewire manages $this->form separately
        if (isset($modelId) && isset($this->model)) {
            $this->form->setModel($this->model);
        }
    }
}
