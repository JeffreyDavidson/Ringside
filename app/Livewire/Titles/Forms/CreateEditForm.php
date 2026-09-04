<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Forms;

use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Livewire\Base\BaseForm;
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
 * @extends BaseForm<Title>
 *
 * @see BaseForm For base form functionality and patterns
 * @see TitleData For typed Action input
 * @see CanChangeDebutDate For custom activation validation
 *
 * @property string $name Championship title name (must end with Title/Titles)
 * @property TitleType|string $type Title type (singles or tag-team)
 * @property Carbon|string|null $start_date Title activation start date
 */
class CreateEditForm extends BaseForm
{
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
     * @var string|null Title type (string to prevent auto-casting issues)
     */
    public ?string $type = '';

    /**
     * Title activation start date for championship history tracking.
     *
     * Tracks when a championship title becomes active and available for
     * competition. Passed through TitleData to the create or update Action.
     *
     * @var string|null Title activation start date (string to prevent auto-casting)
     */
    public ?string $start_date = '';

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
     * @see TitleData::$debut_date For activity period input
     */
    protected function loadModelData(Model $model): void
    {
        $this->start_date = $model->firstActivityPeriod?->started_at?->toDateString();
    }

    /**
     * Store the title data with activity period handling.
     */
    /**
     * Prepare title data for model storage.
     *
     * Transforms form fields into model-compatible data structure.
     * Only includes the title name as activation dates are managed
     * separately through the title's activation relationship system
     * to maintain proper separation of concerns.
     */
    public function toData(): TitleData
    {
        return new TitleData(
            name: $this->name,
            type: TitleType::from((string) $this->type),
            debut_date: $this->start_date ? Carbon::parse($this->start_date) : null,
        );
    }

    public function title(): Title
    {
        return Title::query()->findOrFail($this->modelId);
    }

    /**
     * Get the model class for title form operations.
     *
     * Specifies the Title model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<Title> The Title model class
     */
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
        $title = $this->isEditing() ? $this->title() : null;

        return [
            'name' => ['required', 'string', 'max:255', 'ends_with:Title,Titles', Rule::unique('titles', 'name')->ignore($this->modelId)],
            'type' => ['required', Rule::enum(TitleType::class)],
            'start_date' => ['nullable', 'date', new CanChangeDebutDate($title)],
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
