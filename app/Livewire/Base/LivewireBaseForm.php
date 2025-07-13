<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use EventMatches\EventMatchForm;
use Events\EventForm;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Livewire\Form;
use Wrestlers\WrestlerForm;

/**
 * Abstract base class for Livewire form components.
 *
 * This base form provides common functionality for all Livewire forms in the application,
 * including model binding, form filling, and standardized validation patterns. It serves
 * as a foundation for creating consistent form experiences across different entities.
 *
 * The class establishes a standardized pattern for form management that includes:
 * - Consistent model binding and data population mechanisms
 * - Standardized validation rule and attribute definition patterns
 * - Common utility methods for form display and model interaction
 * - Extensible hooks for custom data loading and transformation
 * - Generic type support for maintaining type safety across implementations
 *
 * Key Design Principles:
 * - Template Method Pattern: Defines the skeleton of form operations while allowing
 *   subclasses to override specific steps (rules, validation attributes, data transformation)
 * - Single Responsibility: Each method has a focused purpose in the form lifecycle
 * - Extensibility: Provides hooks (loadExtraData) for custom implementations
 * - Type Safety: Uses generics to maintain type safety while allowing flexibility
 *
 * The class uses generics to maintain type safety while allowing flexibility for
 * different form implementations and their associated models. Child classes should
 * extend this base to inherit common form behaviors while implementing their
 * specific validation rules and data handling logic.
 *
 * @template TForm of LivewireBaseForm The concrete form class extending this base
 * @template TFormModel of Model|null The Eloquent model type this form manages
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see WrestlerForm For a comprehensive implementation example
 * @see EventForm For a simple implementation example
 * @see EventMatchForm For a complex relationship example
 *
 * @example
 * ```php
 * // Creating a concrete form implementation
 * class WrestlerForm extends LivewireBaseForm
 * {
 *     protected ?Model $formModel = null;
 *
 *     public string $name = '';
 *     public string $hometown = '';
 *     // ... other properties
 *
 *     protected function rules(): array
 *     {
 *         return [
 *             'name' => 'required|string|max:255',
 *             'hometown' => 'required|string|max:255',
 *         ];
 *     }
 *
 *     protected function getModelData(): array
 *     {
 *         return [
 *             'name' => $this->name,
 *             'hometown' => $this->hometown,
 *         ];
 *     }
 * }
 *
 * // Using in a Livewire component
 * class ManageWrestler extends Component
 * {
 *     public WrestlerForm $form;
 *
 *     public function mount(?Wrestler $wrestler = null): void
 *     {
 *         $this->form->setModel($wrestler);
 *     }
 *
 *     public function save(): void
 *     {
 *         $this->form->store();
 *         // Handle success response
 *     }
 * }
 * ```
 */
abstract class LivewireBaseForm extends Form
{
    /**
     * The Eloquent model instance being managed by this form.
     *
     * This property holds the model that the form is either creating (null)
     * or editing (populated model). The generic type TFormModel allows
     * child classes to specify their exact model type while maintaining
     * type safety throughout the application.
     *
     * State Management:
     * - null: Form is in creation mode for new records
     * - Model instance: Form is in edit mode for existing records
     *
     * The model serves as the source of truth for form data population
     * and the target for data persistence operations.
     *
     * @var TFormModel The model instance or null for new records
     */
    protected ?Model $formModel;

    /**
     * The ID of the model being edited, or null for new records.
     *
     * This property persists across Livewire hydration cycles, ensuring
     * that edit mode is maintained even when the formModel object reference
     * is lost during Livewire's serialization/deserialization process.
     *
     * Made public so Livewire can properly hydrate this property.
     *
     * @var int|string|null The model's primary key value
     */
    public int|string|null $modelId = null;

    /**
     * A fallback field name for display purposes.
     *
     * Used by utility methods when a specific field name cannot be determined
     * or when generating default display names for form elements. This provides
     * a graceful fallback for error scenarios or incomplete data situations.
     *
     * @var string Default field name for fallback scenarios
     */
    protected string $fieldName = 'Unknown';

    /**
     * Set the model for this form and populate form fields.
     *
     * This method implements the Template Method pattern by defining the standard
     * flow for model binding while allowing subclasses to customize specific steps
     * through the loadExtraData() hook method.
     *
     * The method performs several critical operations:
     * 1. Binds the model instance to the form state
     * 2. Populates form fields using Livewire's automatic fill mechanism
     * 3. Triggers custom data loading for specialized form requirements
     *
     * When null is passed, the form is prepared for creating a new record with
     * default values. When a model is passed, the form is prepared for editing
     * that record with its current data values populated into form fields.
     *
     * @param  TFormModel  $formModel  The model to bind to this form, or null for new records
     *
     * @see fill() Livewire's automatic form field population
     * @see loadExtraData() Hook for custom data loading implementations
     *
     * @example
     * ```php
     * // Setting up form for editing an existing wrestler
     * $wrestler = Wrestler::find(1);
     * $form->setModel($wrestler);
     * // Form fields are now populated with wrestler data
     * // Custom loadExtraData() is called for additional setup
     *
     * // Setting up form for creating a new wrestler
     * $form->setModel(null);
     * // Form is ready for new record creation with default values
     * ```
     */
    public function setModel(?Model $formModel): void
    {
        $this->formModel = $formModel;
        $this->modelId = $formModel?->getKey();

        // Fill form fields from model attributes if model exists
        if ($formModel) {
            $this->fill($formModel->getAttributes());
        }

        $this->loadExtraData();
    }

    /**
     * Determine if the form is in creation mode.
     *
     * Utility method to check the current form state for conditional logic
     * in form components. Useful for displaying different UI elements,
     * applying different validation rules, or handling different workflows
     * based on whether the form is creating or editing.
     *
     * @return bool True if creating a new record, false if editing existing
     *
     * @example
     * ```php
     * // In a form component
     * public function getSubmitButtonText(): string
     * {
     *     return $this->isCreating() ? 'Create Wrestler' : 'Update Wrestler';
     * }
     *
     * // In validation logic
     * public function store(): bool
     * {
     *     $wasCreating = $this->isCreating();
     *     $result = $this->storeModel();
     *
     *     if ($wasCreating && $result) {
     *         // Handle post-creation logic
     *         $this->handleNewRecordTasks();
     *     }
     *
     *     return $result;
     * }
     * ```
     */
    public function isCreating(): bool
    {
        return $this->modelId === null;
    }

    /**
     * Determine if the form is in editing mode.
     *
     * Utility method to check if the form is currently editing an existing
     * model instance. Complementary to isCreating() for complete state
     * checking capabilities in form logic.
     *
     * @return bool True if editing an existing record, false if creating new
     *
     * @example
     * ```php
     * // In form validation
     * if ($this->isEditing()) {
     *     // Apply edit-specific validation rules
     *     $this->validateEditConstraints();
     * }
     *
     * // In UI rendering - use HTML comment style for Blade examples
     * <!-- In Blade template: -->
     * <!-- @if($form->isEditing()) -->
     * <!--     <p>Last updated: {{ $form->formModel->updated_at }}</p> -->
     * <!-- @endif -->
     * ```
     */
    public function isEditing(): bool
    {
        return $this->formModel !== null;
    }

    /**
     * Generate a display name for editing based on a model field.
     *
     * This utility method safely extracts a field value from the bound model
     * to create user-friendly display names, typically used in form headers,
     * breadcrumbs, page titles, or any UI element that needs to display
     * model-specific information.
     *
     * The method provides several layers of safety:
     * 1. Checks if the model exists (handles creation mode gracefully)
     * 2. Verifies the requested field exists on the model
     * 3. Handles null field values with appropriate fallbacks
     * 4. Converts values to strings for display purposes
     *
     * @param  string  $fieldName  The name of the model field to extract
     * @return string The field value as a string, or 'Unknown' if unavailable
     *
     * @example
     * ```php
     * // In a form component for page titles
     * public function getPageTitle(): string
     * {
     *     if ($this->isEditing()) {
     *         return "Edit " . $this->generateModelEditName('name');
     *         // Returns: "Edit Stone Cold Steve Austin"
     *     }
     *     return "Create New Wrestler";
     * }
     *
     * // Usage in Blade templates for breadcrumbs
     * // <nav aria-label="breadcrumb">
     * //     <ol class="breadcrumb">
     * //         <li><a href="/wrestlers">Wrestlers</a></li>
     * //         <li>{{ $form->generateModelEditName('name') }}</li>
     * //     </ol>
     * // </nav>
     *
     * // For form headers with context
     * // <h1>
     * //     {{ $form->isEditing() ? 'Edit ' . $form->generateModelEditName('name') : 'New Wrestler' }}
     * // </h1>
     * ```
     */
    public function generateModelEditName(string $fieldName): string
    {
        // Handle creation mode - no model available
        if (! $this->formModel) {
            return 'Unknown';
        }

        // Check if the requested field exists on the model
        if (property_exists($this->formModel, $fieldName)) {
            return (string) ($this->formModel->{$fieldName} ?? 'Unknown');
        }

        // Fallback for non-existent fields
        return 'Unknown';
    }

    /**
     * Load additional data specific to the form implementation.
     *
     * This hook method implements part of the Template Method pattern by providing
     * a customization point for subclasses to perform specialized data loading,
     * transformation, or setup operations. It is called automatically after a
     * model is set via setModel(), ensuring that all standard form population
     * has completed before custom logic executes.
     *
     * The method is designed to be overridden by concrete form implementations
     * that need to perform operations beyond simple field population, such as:
     * - Converting stored data formats to form-friendly representations
     * - Loading related model data that isn't automatically filled
     * - Computing derived values for display purposes
     * - Setting up default values for new records
     * - Initializing complex form state based on model relationships
     *
     * Common Implementation Patterns:
     *
     * **Data Transformation**: Converting database storage formats to user-friendly
     * form inputs (e.g., converting stored inches to feet/inches display format)
     *
     * **Relationship Loading**: Populating form fields with data from model
     * relationships (e.g., loading employment start dates, match participants)
     *
     * **Computed Properties**: Calculating display values based on model data
     * (e.g., age from birthdate, career statistics)
     *
     * **Conditional Setup**: Applying different initialization logic based on
     * model state or user permissions
     *
     *
     * @example
     * ```php
     * // In WrestlerForm - Data transformation example
     * public function loadExtraData(): void
     * {
     *     if ($this->formModel) {
     *         // Convert stored height (inches) to feet/inches for display
     *         $height = $this->formModel->height;
     *         $this->height_feet = (int) floor($height / 12);
     *         $this->height_inches = $height % 12;
     *
     *         // Load employment start date from relationship
     *         $this->start_date = $this->formModel->firstEmployment?->started_at;
     *     } else {
     *         // Set defaults for new records
     *         $this->height_feet = 6;
     *         $this->height_inches = 0;
     *     }
     * }
     *
     * // In EventMatchForm - Relationship loading example
     * public function loadExtraData(): void
     * {
     *     if ($this->formModel) {
     *         // Load many-to-many relationships as arrays
     *         $this->wrestler_ids = $this->formModel->wrestlers->pluck('id')->toArray();
     *         $this->referee_ids = $this->formModel->referees->pluck('id')->toArray();
     *
     *         // Load complex relationship data
     *         $this->match_outcome_data = $this->formModel->outcome?->toFormData();
     *     }
     * }
     *
     * // In EventForm - Simple default setup example
     * public function loadExtraData(): void
     * {
     *     if (!$this->formModel) {
     *         // Set reasonable defaults for new events
     *         $this->date = now()->addWeeks(2)->toDateString();
     *         $this->start_time = '19:00';
     *         $this->status = 'scheduled';
     *     }
     * }
     * ```
     *
     * @see WrestlerForm::loadExtraData() For height conversion and employment loading
     * @see EventMatchForm::loadExtraData() For relationship array population
     * @see setModel() For the complete model binding workflow
     */
    protected function loadExtraData(): void
    {
        // Default implementation does nothing
        // Child classes should override this method to implement custom loading logic
    }

    /**
     * Store model data with validation and persistence handling.
     *
     * This method provides the standard workflow for persisting form data to
     * the database. It handles both creation and update operations seamlessly,
     * applying validation and transforming form data into model-compatible
     * format before persistence.
     *
     * The method implements a standardized flow:
     * 1. Validate all form data using defined rules
     * 2. Transform form data using getModelData() method
     * 3. Create or update the model based on current form state
     * 4. Return success status for UI feedback
     *
     * Child classes can override this method to add custom logic such as:
     * - Additional validation steps
     * - Related model creation/updates
     * - Event broadcasting
     * - Cache invalidation
     * - Audit logging
     *
     * @return bool True on successful storage, false on failure
     *
     * @throws ValidationException If validation fails
     *
     * @see getModelData() For data transformation implementation
     * @see rules() For validation rule definition
     * @see WrestlerForm::store() For example with employment relationship handling
     *
     * @example
     * ```php
     * // Basic usage in a Livewire component
     * public function save(): void
     * {
     *     if ($this->form->store()) {
     *         session()->flash('message', 'Record saved successfully!');
     *         $this->redirect('/wrestlers');
     *     }
     * }
     *
     * // Override in child class for custom logic
     * public function store(): bool
     * {
     *     $this->validate();
     *
     *     $wasCreating = $this->isCreating();
     *     $result = $this->storeModel();
     *
     *     if ($result && $wasCreating) {
     *         // Handle post-creation tasks
     *         $this->createInitialEmployment();
     *         $this->sendWelcomeNotification();
     *     }
     *
     *     return $result;
     * }
     * ```
     */
    public function store(): bool
    {
        $this->validate();

        return $this->storeModel();
    }

    /**
     * Submit the form (alias for store method).
     *
     * Provides a convenient alias for the store method to match common
     * form submission patterns and testing expectations.
     *
     * @return bool True on successful submission, false otherwise
     */
    public function submit(): bool
    {
        return $this->store();
    }

    /**
     * Perform the actual model persistence operation.
     *
     * This method handles the low-level database operations for both creating
     * new models and updating existing ones. It uses the getModelData() method
     * to transform form data into model-compatible format and applies the
     * appropriate persistence strategy based on the current form state.
     *
     * The method abstracts away the differences between creation and update
     * operations, providing a consistent interface for child classes while
     * handling the underlying Eloquent operations appropriately.
     *
     * For creation operations:
     * - Creates a new model instance with the provided data
     * - Saves the new instance to the database
     * - Updates formModel property with the new instance
     *
     * For update operations:
     * - Updates the existing model with new data
     * - Saves changes to the database
     * - Maintains the existing model reference
     *
     * @return bool True if the model was successfully saved, false otherwise
     *
     * @throws Exception If model creation or update fails
     *
     * @see getModelData() For data transformation requirements
     * @see isCreating() For operation mode detection
     *
     * @example
     * ```php
     * // Called automatically by store() method
     * public function store(): bool
     * {
     *     $this->validate();
     *
     *     // This will call storeModel() internally
     *     $result = $this->storeModel();
     *
     *     if ($result) {
     *         // Handle successful save
     *         $this->handlePostSaveOperations();
     *     }
     *
     *     return $result;
     * }
     * ```
     */
    protected function storeModel(): bool
    {
        $data = $this->getModelData();

        if ($this->isCreating()) {
            // Create new model instance
            $modelClass = $this->getModelClass();
            $this->formModel = $modelClass::create($data);
            $this->modelId = $this->formModel->getKey();
        } else {
            // Update existing model
            // Ensure we have the model instance - reload if necessary
            if (! $this->formModel && $this->modelId) {
                $modelClass = $this->getModelClass();
                $this->formModel = $modelClass::findOrFail($this->modelId);
            }

            $this->formModel->update($data);
        }

        return true;
    }

    /**
     * Transform form data into model-compatible format.
     *
     * This abstract method must be implemented by child classes to define how
     * form fields are transformed into data suitable for model persistence.
     * The method serves as a data transformation layer between the form's
     * user-friendly representation and the model's storage requirements.
     *
     * The method should return an associative array where keys correspond to
     * model attributes and values are the transformed form data. This allows
     * for complex data transformations, calculations, and format conversions
     * before database storage.
     *
     * Common transformation patterns include:
     * - Converting display formats to storage formats
     * - Combining multiple form fields into single model attributes
     * - Applying business logic calculations
     * - Filtering out fields that shouldn't be mass-assigned
     * - Converting user input to appropriate data types
     *
     * @return array<string, mixed> Model data ready for persistence
     *
     * @example
     * ```php
     * // In WrestlerForm - Complex transformation example
     * protected function getModelData(): array
     * {
     *     // Convert feet/inches to total inches for storage
     *     $height = new Height($this->height_feet, $this->height_inches);
     *
     *     return [
     *         'name' => $this->name,
     *         'hometown' => $this->hometown,
     *         'height' => $height->toInches(), // Transform to storage format
     *         'weight' => $this->weight,
     *         'signature_move' => $this->signature_move,
     *         // Note: employment data excluded - handled separately
     *     ];
     * }
     *
     * // In EventForm - Simple mapping example
     * protected function getModelData(): array
     * {
     *     return [
     *         'name' => $this->name,
     *         'date' => $this->date,
     *         'venue' => $this->venue,
     *         'capacity' => $this->capacity,
     *         'ticket_price' => $this->ticket_price * 100, // Convert to cents
     *     ];
     * }
     *
     * // In EventMatchForm - Relationship exclusion example
     * protected function getModelData(): array
     * {
     *     return [
     *         'event_id' => $this->event_id,
     *         'match_type' => $this->match_type,
     *         'scheduled_duration' => $this->scheduled_duration,
     *         'notes' => $this->notes,
     *         // wrestler_ids handled separately in relationships
     *     ];
     * }
     * ```
     *
     * @see storeModel() For usage in persistence workflow
     * @see WrestlerForm::getModelData() For complex transformation example
     */
    abstract protected function getModelData(): array;

    /**
     * Define validation rules for form fields.
     *
     * This abstract method must be implemented by child classes to specify
     * the validation rules for their form fields. The method should return
     * an array of Laravel validation rules that will be applied during the
     * form submission process.
     *
     * The validation rules array follows Laravel's standard format where
     * keys are field names and values are arrays of validation rules.
     * Rules can include built-in Laravel validators, custom rule objects,
     * conditional rules, and database uniqueness constraints.
     *
     * Child classes should implement comprehensive validation including:
     * - Required field validation
     * - Data type and format validation
     * - Uniqueness constraints with proper model exclusion for updates
     * - Custom business logic validation
     * - Conditional validation based on form state
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     *
     * @example
     * ```php
     * // In WrestlerForm - Comprehensive validation example
     * protected function rules(): array
     * {
     *     return [
     *         'name' => [
     *             'required',
     *             'string',
     *             'max:255',
     *             Rule::unique('wrestlers', 'name')->ignore($this->formModel)
     *         ],
     *         'hometown' => ['required', 'string', 'max:255'],
     *         'height_feet' => ['required', 'integer', 'min:5', 'max:7'],
     *         'height_inches' => ['required', 'integer', 'min:0', 'max:11'],
     *         'weight' => ['required', 'integer', 'digits:3'],
     *         'signature_move' => [
     *             'nullable',
     *             'string',
     *             'max:255',
     *             Rule::unique('wrestlers', 'signature_move')->ignore($this->formModel)
     *         ],
     *         'start_date' => [
     *             'nullable',
     *             'date',
     *             new CanChangeEmploymentDate($this->formModel)
     *         ],
     *     ];
     * }
     *
     * // In EventForm - Simpler validation example
     * protected function rules(): array
     * {
     *     return [
     *         'name' => ['required', 'string', 'max:255'],
     *         'date' => ['required', 'date', 'after:today'],
     *         'venue' => ['required', 'string', 'max:255'],
     *         'capacity' => ['required', 'integer', 'min:50'],
     *     ];
     * }
     * ```
     *
     * @see store() For validation usage in storage workflow
     * @see validationAttributes() For custom field name definitions
     */
    abstract protected function rules(): array;

    /**
     * Define user-friendly attribute names for validation messages.
     *
     * This method allows child classes to provide custom field names that
     * will be used in validation error messages instead of the actual form
     * property names. This improves user experience by showing intuitive,
     * readable field names in error messages.
     *
     * The method should return an array where keys are form property names
     * and values are the user-friendly names that should appear in validation
     * error messages. Only fields that need custom names need to be included;
     * fields not specified will use their property names as-is.
     *
     * This is particularly useful for:
     * - Converting technical field names to user-friendly terms
     * - Providing proper capitalization and spacing
     * - Using domain-specific terminology that users understand
     * - Improving accessibility and user experience
     *
     * @return array<string, string> Field name mappings for validation messages
     *
     * @example
     * ```php
     * // In WrestlerForm - Technical to friendly mapping
     * protected function validationAttributes(): array
     * {
     *     return [
     *         'height_feet' => 'feet',
     *         'height_inches' => 'inches',
     *         'signature_move' => 'signature move',
     *         'start_date' => 'start date',
     *     ];
     * }
     * // Error message: "The feet field is required" instead of "The height_feet field is required"
     *
     * // In EventMatchForm - Domain terminology example
     * protected function validationAttributes(): array
     * {
     *     return [
     *         'wrestler_ids' => 'participating wrestlers',
     *         'referee_id' => 'assigned referee',
     *         'match_type' => 'match type',
     *         'scheduled_duration' => 'scheduled duration',
     *     ];
     * }
     * ```
     *
     * @see rules() For validation rule definitions
     */
    protected function validationAttributes(): array
    {
        return [];
    }

    /**
     * Get the model class name for this form.
     *
     * This method should return the fully qualified class name of the model
     * that this form manages. Used for creating new model instances.
     *
     * @return string The fully qualified model class name
     *
     * @example
     * ```php
     * protected function getModelClass(): string
     * {
     *     return Event::class;
     * }
     * ```
     */
    abstract protected function getModelClass(): string;
}
