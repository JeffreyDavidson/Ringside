<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Forms;

use App\Enums\Titles\TitleType;
use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\ManagesActivityPeriods;
use App\Models\Titles\Title;
use App\Rules\Shared\CanChangeDebutDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Livewire form component for managing championship title creation and editing.
 *
 * This form handles championship title management including title identification,
 * naming conventions, and activation period tracking. Championship titles represent
 * wrestling belts and honors that wrestlers compete for, requiring careful tracking
 * of when titles are active, retired, or temporarily inactive.
 *
 * Key Responsibilities:
 * - Championship title creation and naming with wrestling conventions
 * - Title uniqueness enforcement across all championships
 * - Activation period tracking for title history and lineage
 * - Wrestling-specific validation (titles must end with "Title" or "Titles")
 * - Integration with title activation relationship system
 *
 * @extends LivewireBaseForm<TitleForm, Title>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality and patterns
 * @see ManagesActivityPeriods For activation period tracking
 * @see CanChangeDebutDate For custom activation validation
 *
 * @property string $name Championship title name (must end with Title/Titles)
 * @property TitleType|string $type Title type (singles or tag-team)
 * @property Carbon|string|null $start_date Title activation start date
 */
class TitleForm extends LivewireBaseForm
{
    use ManagesActivityPeriods;

    /**
     * The model instance being edited, or null for new title creation.
     *
     * @var Title|null Current title model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Championship title's official name following wrestling conventions.
     *
     * Must end with "Title" or "Titles" to follow standard wrestling
     * nomenclature (e.g., "Heavyweight Championship Title", "Tag Team Titles").
     * Used in match announcements, promotional materials, and championship
     * records. Must be unique across all titles in the system.
     *
     * @var string Championship title name with required suffix
     */
    public string $name = '';

    /**
     * Championship title type classification.
     *
     * Defines whether the title is for individual competitors (singles)
     * or tag team competitors (tag-team). This affects championship
     * rules, match types, and who can compete for the title.
     *
     * @var TitleType|string Title type (singles or tag-team)
     */
    public TitleType|string $type = '';

    /**
     * Title activation start date for championship history tracking.
     *
     * Tracks when a championship title becomes active and available for
     * competition. Managed through ManagesActivityPeriods trait for
     * consistent activation tracking across the title system.
     *
     * @var Carbon|string|null Title activation start date
     */
    public Carbon|string|null $start_date = '';

    /**
     * Load additional data when editing existing title records.
     *
     * Handles activation period data loading for edit operations,
     * retrieving the first activation date from the title's activation
     * relationship system for display in the form.
     *
     * Activation Integration:
     * - Loads start date from first activation relationship
     * - Handles null activations for titles not yet activated
     * - Converts Carbon dates to string format for form display
     *
     *
     * @see ManagesActivityPeriods For activation period management
     */
    public function loadExtraData(): void
    {
        // Only process if we have a title model
        if (! $this->formModel instanceof Title) {
            return;
        }

        // Load activation start date from first activity period relationship
        $this->start_date = $this->formModel->firstActivityPeriod?->started_at?->toDateString();
    }

    /**
     * Handle additional tasks after title creation.
     *
     * Creates activation record for new titles with start dates.
     * Called automatically by the store pattern trait.
     */
    protected function handlePostCreationTasks(): void
    {
        // Create activation record for new titles with start dates
        if ($this->start_date) {
            $this->handleActivityPeriodCreation();
        }
    }

    /**
     * Prepare title data for model storage.
     *
     * Transforms form fields into model-compatible data structure.
     * Only includes the title name as activation dates are managed
     * separately through the title's activation relationship system
     * to maintain proper separation of concerns.
     *
     * @return array<string, mixed> Model data ready for persistence
     */
    protected function getModelData(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
        ];
        // Note: start_date is NOT included here because activation dates
        // are managed separately through the title's activation relationship system
    }

    /**
     * Get the model class for title form operations.
     *
     * Specifies the Title model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<Title> The Title model class
     */
    protected function getModelClass(): string
    {
        return Title::class;
    }

    /**
     * Define validation rules for championship title fields.
     *
     * Provides comprehensive validation for championship titles including
     * wrestling industry naming conventions, uniqueness constraints, and
     * activation date validation through custom rules.
     *
     * Validation Requirements:
     * - Name: Required, unique, max 255 characters, must end with Title/Titles
     * - Wrestling Convention: Enforces standard championship naming patterns
     * - Start Date: Optional, valid date, custom activation validation
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     *
     * @see CanChangeDebutDate For custom date validation
     * @see Rule::unique() For database uniqueness constraints
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'ends_with:Title,Titles', Rule::unique('titles', 'name')->ignore($this->formModel)],
            'type' => ['required', Rule::enum(TitleType::class)],
            'start_date' => ['nullable', 'date', new CanChangeDebutDate($this->formModel)],
        ];
    }

    /**
     * Get title-specific validation attributes.
     *
     * All standard attributes are provided by HasStandardValidationAttributes trait.
     * This method handles title-specific field naming.
     *
     * @return array<string, string> Custom validation attributes for this form
     */
    protected function getCustomValidationAttributes(): array
    {
        return [
            'type' => 'title type',
            'start_date' => 'start date',
        ];
    }
}
