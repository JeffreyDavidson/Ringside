<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Forms;

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\ManagesEmployment;
use App\Models\Managers\Manager;
use App\Rules\Shared\CanChangeEmploymentDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Livewire form component for managing manager creation and editing.
 *
 * This form handles wrestling manager personnel management including personal
 * identification, name management, and employment tracking integration. Managers
 * are key wrestling personalities who represent and guide wrestlers in storylines
 * and business matters, requiring careful tracking of their employment status,
 * personal information, and availability for wrestling programming.
 *
 * Key Responsibilities:
 * - Manager personal identification (first and last name)
 * - Employment relationship tracking for manager contracts
 * - Personal information validation and data integrity
 * - Integration with wrestler representation and storyline systems
 * - Personnel record management for wrestling entertainment operations
 *
 * @extends LivewireBaseForm<ManagerForm, Manager>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality and patterns
 * @see ManagesEmployment For employment tracking capabilities
 * @see CanChangeEmploymentDate For custom validation rules
 *
 * @property string $first_name Manager's first name for identification
 * @property string $last_name Manager's last name for identification
 * @property Carbon|string|null $employment_date Employment start date
 */
class ManagerForm extends LivewireBaseForm
{
    use ManagesEmployment;

    /**
     * The model instance being edited, or null for new manager creation.
     *
     * @var Manager|null Current manager model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Manager's first name for personal identification.
     *
     * Used for character development, storyline integration, promotional
     * materials, employment documentation, and operational coordination.
     * Combined with last name to create complete manager identification
     * for wrestling programming and business operations.
     *
     * @var string Manager's first name
     */
    public string $first_name = '';

    /**
     * Manager's last name for personal identification.
     *
     * Used for character development, storyline integration, promotional
     * materials, employment documentation, and operational coordination.
     * Combined with first name to create complete manager identification
     * for wrestling programming and business operations.
     *
     * @var string Manager's last name
     */
    public string $last_name = '';

    /**
     * Employment start date for manager contract tracking.
     *
     * Managed through ManagesEmployment trait for consistent employment
     * tracking across all personnel types. Critical for storyline planning,
     * payroll management, benefits administration, and availability
     * scheduling for wrestling programming and events.
     *
     * @var Carbon|string|null Manager employment start date
     */
    public Carbon|string|null $employment_date = null;

    /**
     * Load additional data when editing existing manager records.
     *
     * Handles employment relationship data loading for edit operations,
     * retrieving employment start date from the manager's employment
     * relationship for display and modification in the form. Essential
     * for maintaining accurate employment records.
     *
     * Employment Integration:
     * - Loads start date from employment relationship
     * - Handles null employment for managers not yet contracted
     * - Converts Carbon dates to string format for form display
     *
     *
     * @see ManagesEmployment::$employment_date For employment date handling
     */
    public function loadExtraData(): void
    {
        // Only process if we have a manager model
        if (! $this->formModel instanceof Manager) {
            return;
        }

        // Load employment start date from relationship
        $this->employment_date = $this->formModel->firstEmployment?->started_at?->toDateString();
    }

    /**
     * Prepare manager data for model storage.
     *
     * Transforms form fields into model-compatible data structure.
     * Includes personal identification data while excluding employment
     * information which is handled separately through the employment
     * relationship system for proper data separation and integrity.
     *
     * @return array<string, mixed> Model data ready for persistence
     */
    protected function getModelData(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ];
        // Note: employment data is managed separately through
        // the employment relationship system
    }

    /**
     * Get the model class for manager form operations.
     *
     * Specifies the Manager model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<Manager> The Manager model class
     */
    protected function getModelClass(): string
    {
        return Manager::class;
    }

    /**
     * Define validation rules for manager form fields.
     *
     * Provides comprehensive validation for manager personal data including
     * name requirements and employment date validation through custom rules.
     * Ensures manager records have complete identification information for
     * operational, storyline, and administrative purposes.
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'employment_date' => ['nullable', 'date', new CanChangeEmploymentDate($this->formModel)],
        ];
    }

    /**
     * Get manager-specific validation attributes.
     *
     * Extends standard attributes with manager-specific field names for better
     * user experience in validation messages.
     *
     * @return array<string, string> Custom validation attributes for this form
     */
    protected function validationAttributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'employment_date' => 'employment date',
        ];
    }
}
