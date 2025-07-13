<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Forms;

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\ManagesEmployment;
use App\Models\Referees\Referee;
use App\Rules\Shared\CanChangeEmploymentDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Livewire form component for managing referee creation and editing.
 *
 * This form handles referee personnel management including personal identification,
 * name management, and employment tracking integration. Referees are essential
 * match officials who oversee wrestling contests, requiring careful tracking of
 * their employment status, availability, and personal information for match
 * assignment and operational management.
 *
 * Key Responsibilities:
 * - Referee personal identification (first and last name)
 * - Employment relationship tracking for referee contracts
 * - Personal information validation and data integrity
 * - Integration with match assignment and scheduling systems
 * - Official personnel record management for wrestling operations
 *
 * @extends LivewireBaseForm<RefereeForm, Referee>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality and patterns
 * @see ManagesEmployment For employment tracking capabilities
 * @see CanChangeEmploymentDate For custom validation rules
 *
 * @property string $first_name Referee's first name for identification
 * @property string $last_name Referee's last name for identification
 * @property Carbon|string|null $employment_date Employment start date
 */
class RefereeForm extends LivewireBaseForm
{
    use ManagesEmployment;

    /**
     * The model instance being edited, or null for new referee creation.
     *
     * @var Referee|null Current referee model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Referee's first name for personal identification.
     *
     * Used for official match records, employment documentation, payroll
     * systems, and match assignment coordination. Combined with last name
     * to create complete referee identification for operational management
     * and official wrestling event documentation.
     *
     * @var string Referee's first name
     */
    public string $first_name = '';

    /**
     * Referee's last name for personal identification.
     *
     * Used for official match records, employment documentation, payroll
     * systems, and match assignment coordination. Combined with first name
     * to create complete referee identification for operational management
     * and official wrestling event documentation.
     *
     * @var string Referee's last name
     */
    public string $last_name = '';

    /**
     * Employment start date for referee contract tracking.
     *
     * Managed through ManagesEmployment trait for consistent employment
     * tracking across all personnel types. Essential for payroll, benefits,
     * scheduling availability, and operational planning for wrestling events
     * requiring qualified officiating staff.
     *
     * @var Carbon|string|null Referee employment start date
     */
    public Carbon|string|null $employment_date = null;

    /**
     * Load additional data when editing existing referee records.
     *
     * Handles employment relationship data loading for edit operations,
     * retrieving employment start date from the referee's employment
     * relationship for display and modification in the form.
     *
     * Employment Integration:
     * - Loads start date from employment relationship
     * - Handles null employment for referees not yet contracted
     * - Converts Carbon dates to string format for form display
     *
     *
     * @see ManagesEmployment::$employment_date For employment date handling
     */
    public function loadExtraData(): void
    {
        // Only process if we have a referee model
        if (! $this->formModel instanceof Referee) {
            return;
        }

        // Load employment start date from relationship
        $this->employment_date = $this->formModel->firstEmployment?->started_at?->toDateString();
    }

    /**
     * Prepare referee data for model storage.
     *
     * Transforms form fields into model-compatible data structure.
     * Includes personal identification data while excluding employment
     * information which is handled separately through the employment
     * relationship system for proper data separation.
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
     * Get the model class for referee form operations.
     *
     * Specifies the Referee model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<Referee> The Referee model class
     */
    protected function getModelClass(): string
    {
        return Referee::class;
    }

    /**
     * Define validation rules for referee form fields.
     *
     * Provides comprehensive validation for referee personal data including
     * name requirements and employment date validation through custom rules.
     * Ensures referee records have complete identification information for
     * operational and administrative purposes.
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'employment_date' => 'nullable', 'date', new CanChangeEmploymentDate($this->formModel),
        ];
    }

    /**
     * Get referee-specific validation attributes.
     *
     * Extends standard attributes with referee-specific field names for better
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
