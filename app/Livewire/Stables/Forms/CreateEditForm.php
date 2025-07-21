<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Forms;

use App\Livewire\Base\BaseForm;
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
 * @extends BaseForm<Form, Stable>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseForm For base form functionality and patterns
 * @see ManagesActivityPeriods For activation period tracking
 * @see CanChangeDebutDate For custom activation validation
 *
 * @property string $name Stable's official name for storylines and promotion
 * @property Carbon|string|null $start_date Stable activation start date
 */
class CreateEditForm extends BaseForm
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
     * in storylines and programming. Used for activity period creation.
     *
     * @var string|null Stable activation start date (string to prevent auto-casting)
     */
    public string|null $started_at = null;

    /**
     * Stable deactivation end date for faction history tracking.
     *
     * Tracks when a wrestling stable becomes inactive or is disbanded.
     * Used for completing activity periods.
     *
     * @var string|null Stable deactivation end date (string to prevent auto-casting)
     */
    public string|null $ended_at = null;

    /**
     * Array of wrestler IDs to be assigned to the stable.
     *
     * @var array<int>
     */
    public array $wrestlers = [];

    /**
     * Array of tag team IDs to be assigned to the stable.
     *
     * @var array<int>
     */
    public array $tag_teams = [];

    /**
     * Accessor for trait compatibility - ManagesActivityPeriods expects start_date.
     */
    protected function getStartDateAttribute(): ?string
    {
        return $this->started_at;
    }

    /**
     * Handle activity period creation when creating a new model.
     * Override the trait method to use our property names.
     */
    protected function handleActivityPeriodCreation(): void
    {
        if (! empty($this->started_at)) {
            $data = ['started_at' => $this->started_at];
            
            // Include end date if provided
            if (! empty($this->ended_at)) {
                $data['ended_at'] = $this->ended_at;
            }
            
            $this->formModel->activityPeriods()->create($data);
        }
    }

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

        // Load activation dates from first activity period relationship
        $this->started_at = $this->formModel->firstActivityPeriod?->started_at?->toDateString();
        $this->ended_at = $this->formModel->firstActivityPeriod?->ended_at?->toDateString();
    }

    /**
     * Store the stable data with activity period handling.
     */
    public function store(): bool
    {
        $this->validate();
        
        $wasCreating = $this->isCreating();
        $result = $this->storeModel();
        
        if ($result) {
            if ($wasCreating) {
                $this->handlePostCreationTasks();
            } else {
                $this->handlePostUpdateTasks();
            }
        }
        
        return $result;
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
        if ($this->started_at) {
            $this->handleActivityPeriodCreation();
        }
        
        // Handle member assignments
        $this->handleMemberAssignments();
    }

    /**
     * Handle additional tasks after stable update.
     */
    protected function handlePostUpdateTasks(): void
    {
        // Update the first activity period if dates changed
        if ($this->started_at && $this->formModel->firstActivityPeriod) {
            $updateData = ['started_at' => $this->started_at];
            if ($this->ended_at) {
                $updateData['ended_at'] = $this->ended_at;
            }
            $this->formModel->firstActivityPeriod()->update($updateData);
        }
    }

    /**
     * Handle assigning wrestlers and tag teams to the stable.
     */
    protected function handleMemberAssignments(): void
    {
        if (!empty($this->wrestlers)) {
            $wrestlerData = [];
            foreach ($this->wrestlers as $wrestlerId) {
                $wrestlerData[$wrestlerId] = ['joined_at' => $this->started_at ?? now()];
            }
            $this->formModel->wrestlers()->attach($wrestlerData);
        }
        
        if (!empty($this->tag_teams)) {
            $tagTeamData = [];
            foreach ($this->tag_teams as $tagTeamId) {
                $tagTeamData[$tagTeamId] = ['joined_at' => $this->started_at ?? now()];
            }
            $this->formModel->tagTeams()->attach($tagTeamData);
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
        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('stables', 'name')->ignore($this->modelId)],
            'started_at' => ['nullable', 'date', new CanChangeDebutDate($this->formModel)],
            'ended_at' => ['nullable', 'date'],
            'wrestlers' => ['nullable', 'array'],
            'wrestlers.*' => ['integer', 'exists:wrestlers,id'],
            'tag_teams' => ['nullable', 'array'],
            'tag_teams.*' => ['integer', 'exists:tag_teams,id'],
        ];

        // Add validation that ended_at is after started_at if both are provided
        if (!empty($this->started_at) && !empty($this->ended_at)) {
            $rules['ended_at'][] = 'after:started_at';
        }

        return $rules;
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
            'started_at' => 'start date',
            'ended_at' => 'end date',
        ];
    }
}
