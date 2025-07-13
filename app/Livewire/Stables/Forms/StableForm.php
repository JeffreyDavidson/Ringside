<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Forms;

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\ManagesActivityPeriods;
use App\Models\Stables\Stable;
use App\Rules\Shared\CanChangeDebutDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Livewire form component for managing stable creation and editing.
 *
 * This form handles stable (wrestling faction) management including group
 * identification, naming conventions, and activation period tracking. Stables
 * represent groups of wrestlers who work together in storylines and matches,
 * requiring careful tracking of when groups are active, disbanded, or reformed
 * throughout wrestling programming cycles.
 *
 * Key Responsibilities:
 * - Stable identification and naming with minimum length requirements
 * - Group uniqueness enforcement across all wrestling factions
 * - Activation period tracking for stable history and storyline continuity
 * - Minimum name length validation for meaningful group identification
 * - Integration with stable activation relationship system
 * - Wrestling storyline and faction management support
 *
 * @extends LivewireBaseForm<StableForm, Stable>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality and patterns
 * @see ManagesActivityPeriods For activation period tracking
 * @see CanChangeDebutDate For custom activation validation
 *
 * @property string $name Stable's official name for storylines and promotion
 * @property Carbon|string|null $start_date Stable activation start date
 */
class StableForm extends LivewireBaseForm
{
    use ManagesActivityPeriods;

    /**
     * The model instance being edited, or null for new stable creation.
     *
     * @var Stable|null Current stable model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Stable's official name for storylines and promotional materials.
     *
     * Used in wrestling storylines, promotional content, match announcements,
     * and faction-based programming. Must be unique across all stables and
     * have sufficient length (minimum 5 characters) for meaningful identification
     * and fan recognition in complex storyline development.
     *
     * @var string Stable's primary name identifier
     */
    public string $name = '';

    /**
     * Stable activation start date for faction history tracking.
     *
     * Tracks when a wrestling stable becomes active and begins appearing
     * in storylines and programming. Managed through ManagesActivityPeriods
     * trait for consistent activation tracking across the stable system,
     * allowing for stable reformations and storyline continuity.
     *
     * @var Carbon|string|null Stable activation start date
     */
    public Carbon|string|null $start_date = null;

    /**
     * Load additional data when editing existing stable records.
     *
     * Handles activation period data loading for edit operations,
     * retrieving the first activation date from the stable's activation
     * relationship system for display in the form. Essential for tracking
     * stable history and storyline continuity.
     *
     * Activation Integration:
     * - Loads start date from first activation relationship
     * - Handles null activations for stables not yet activated
     * - Converts Carbon dates to string format for form display
     * - Supports stable reformation and reactivation scenarios
     *
     *
     * @see ManagesActivityPeriods For activation period management
     */
    public function loadExtraData(): void
    {
        // Only process if we have a stable model
        if (! $this->formModel instanceof Stable) {
            return;
        }

        // Load activation start date from first activity period relationship
        $this->start_date = $this->formModel->firstActivityPeriod?->started_at?->toDateString();
    }

    /**
     * Handle additional tasks after stable creation.
     *
     * Creates activation record for new stables with start dates.
     * Called automatically by the store pattern trait.
     */
    protected function handlePostCreationTasks(): void
    {
        // Create activation record for new stables with start dates
        if ($this->start_date) {
            $this->handleActivityPeriodCreation();
        }
    }

    /**
     * Prepare stable data for model storage.
     *
     * Transforms form fields into model-compatible data structure.
     * Only includes the stable name as activation dates are managed
     * separately through the stable's activation relationship system
     * to maintain proper separation of concerns in faction management.
     *
     * @return array<string, mixed> Model data ready for persistence
     */
    protected function getModelData(): array
    {
        return [
            'name' => $this->name,
        ];
        // Note: start_date is NOT included here because activation dates
        // are managed separately through the stable's activation relationship system
    }

    /**
     * Get the model class for stable form operations.
     *
     * Specifies the Stable model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<Stable> The Stable model class
     */
    protected function getModelClass(): string
    {
        return Stable::class;
    }

    /**
     * Define validation rules for stable form fields.
     *
     * Provides comprehensive validation for wrestling stables including
     * minimum name length requirements, uniqueness constraints, and
     * activation date validation through custom rules. Ensures stables
     * have meaningful names for storyline development and fan recognition.
     *
     * Validation Requirements:
     * - Name: Required, unique, minimum 5 characters, max 255 characters
     * - Minimum Length: Ensures meaningful stable names for storylines
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
            'name' => ['required', 'string', 'max:255', Rule::unique('stables', 'name')->ignore($this->formModel)],
            'start_date' => ['nullable', 'date', new CanChangeDebutDate($this->formModel)],
        ];
    }

    /**
     * Get stable-specific validation attributes.
     *
     * All standard attributes are provided by HasStandardValidationAttributes trait.
     * This method handles stable-specific field naming.
     *
     * @return array<string, string> Custom validation attributes for this form
     */
    protected function getCustomValidationAttributes(): array
    {
        return [
            'start_date' => 'start date',
        ];
    }
}
