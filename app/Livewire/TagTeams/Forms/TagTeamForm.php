<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Forms;

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\ManagesEmployment;
use App\Models\TagTeams\TagTeam;
use App\Rules\Shared\CanChangeEmploymentDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Livewire form component for managing tag team creation and editing.
 *
 * This form handles the complete lifecycle of tag team partnership management
 * including team identification, wrestler relationships, signature moves, and
 * employment tracking integration. Provides specialized validation for
 * wrestling tag team requirements and relationship management.
 *
 * Key Responsibilities:
 * - Tag team profile management (name, signature moves)
 * - Wrestler relationship tracking and validation
 * - Manager relationship tracking and assignment
 * - Employment relationship tracking and validation
 * - Tag team partnership data (formation dates, career information)
 * - Custom validation rules for wrestling tag team requirements
 *
 * @extends LivewireBaseForm<TagTeamForm, TagTeam>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality and patterns
 * @see ManagesEmployment For employment tracking capabilities
 * @see CanChangeEmploymentDate For custom validation rules
 *
 * @property string $name Tag team's official name
 * @property string|null $signature_move Tag team's finishing move or signature
 * @property int|null $wrestlerA First wrestler ID in the tag team
 * @property int|null $wrestlerB Second wrestler ID in the tag team
 * @property array<int, int> $managers Array of manager IDs assigned to the tag team
 * @property Carbon|string|null $employment_date Employment start date
 */
class TagTeamForm extends LivewireBaseForm
{
    use ManagesEmployment;

    /**
     * The model instance being edited, or null for new tag team creation.
     *
     * @var TagTeam|null Current tag team model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Tag team's official name for identification and promotion.
     *
     * Used for match announcements, promotional materials, and team
     * identification. Must be unique across all tag teams in the system.
     *
     * @var string Tag team's primary name identifier
     */
    public string $name = '';

    /**
     * Tag team's signature finishing move or special technique.
     *
     * Optional field for tag team persona development. Represents the
     * coordinated finishing move that the team uses to win matches.
     * Must be unique if provided to avoid confusion in match commentary.
     *
     * @var string|null Tag team signature wrestling move name
     */
    public ?string $signature_move = '';

    /**
     * First wrestler ID in the tag team partnership.
     *
     * References the first wrestler in the tag team. Both wrestlers
     * must be different and valid wrestler IDs in the system.
     *
     * @var int|null First wrestler's ID
     */
    public ?int $wrestlerA = null;

    /**
     * Second wrestler ID in the tag team partnership.
     *
     * References the second wrestler in the tag team. Must be different
     * from wrestlerA and a valid wrestler ID in the system.
     *
     * @var int|null Second wrestler's ID
     */
    public ?int $wrestlerB = null;

    /**
     * Array of manager IDs assigned to the tag team.
     *
     * Represents the managers currently associated with the tag team.
     * Supports multiple managers for comprehensive tag team management.
     * Each ID must reference a valid manager in the system.
     *
     * @var array<int, int> Array of manager IDs
     */
    public array $managers = [];

    /**
     * Employment start date for contract and career tracking.
     *
     * Managed through ManagesEmployment trait for consistent employment
     * tracking across all personnel types. Supports Carbon objects or
     * string dates for flexible input handling.
     *
     * @var Carbon|string|null Employment start date
     */
    public Carbon|string|null $employment_date = '';

    /**
     * Load additional data when editing existing tag team records.
     *
     * Handles complex data loading including employment relationships
     * and wrestler relationships. Called automatically during form
     * initialization for edit operations.
     *
     * Employment Integration:
     * - Loads start date from employment relationship
     * - Handles null employment for new tag teams
     *
     * Wrestler Relationships:
     * - Loads current wrestler assignments
     * - Handles relationship changes and updates
     *
     *
     * @see ManagesEmployment::$start_date For employment date handling
     */
    public function loadExtraData(): void
    {
        // Only process if we have a tag team model
        if (! $this->formModel instanceof TagTeam) {
            return;
        }

        // Load employment start date from relationship (with type safety)
        if ($this->formModel->hasEmployments()) {
            $this->employment_date = $this->formModel->firstEmployment?->started_at?->toDateString();
        }

        // Load current wrestler assignments
        $currentWrestlers = $this->formModel->currentWrestlers;
        if ($currentWrestlers->isNotEmpty()) {
            $this->wrestlerA = $currentWrestlers->first()->getKey();
            $this->wrestlerB = $currentWrestlers->skip(1)->first()->getKey();
        }

        // Load current manager assignments
        $this->managers = $this->formModel->currentManagers->pluck('id')->toArray();
    }

    /**
     * Handle additional tasks after tag team creation.
     *
     * Manages wrestler relationship synchronization and employment setup
     * for new tag teams. Called automatically by the store pattern trait.
     */
    protected function handlePostCreationTasks(): void
    {
        // Create employment record for new tag teams with start dates
        if ($this->employment_date) {
            $this->handleEmploymentCreation();
        }

        // Handle wrestler relationships
        if ($this->formModel instanceof TagTeam) {
            $this->updateWrestlerRelationships();
            $this->updateManagerRelationships();
        }
    }

    /**
     * Update wrestler relationships for the tag team.
     *
     * Manages the many-to-many relationship between the tag team and
     * its wrestler members. Ensures proper relationship synchronization.
     */
    private function updateWrestlerRelationships(): void
    {
        if (! $this->formModel instanceof TagTeam) {
            return;
        }

        $wrestlerIds = array_filter([$this->wrestlerA, $this->wrestlerB]);
        $this->formModel->wrestlers()->sync($wrestlerIds);
    }

    /**
     * Update manager relationships for the tag team.
     *
     * Manages the many-to-many relationship between the tag team and
     * its assigned managers. Ensures proper relationship synchronization.
     */
    private function updateManagerRelationships(): void
    {
        if (! $this->formModel instanceof TagTeam) {
            return;
        }

        $this->formModel->managers()->sync($this->managers);
    }

    /**
     * Prepare tag team-specific data for model storage.
     *
     * Transforms form fields into model-compatible data structure.
     * Excludes employment and wrestler relationship data which are
     * handled separately through their respective systems.
     *
     * Data Transformations:
     * - Passes through tag team fields with appropriate typing
     * - Excludes wrestler IDs (handled via relationships)
     * - Excludes employment data (handled separately)
     *
     * @return array<string, mixed> Model data ready for persistence
     */
    protected function getModelData(): array
    {
        return [
            'name' => $this->name,
            'signature_move' => $this->signature_move,
        ];
        // Note: wrestler relationships and employment data handled separately
    }

    /**
     * Get the model class for tag team form operations.
     *
     * Specifies the TagTeam model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<TagTeam> The TagTeam model class
     */
    protected function getModelClass(): string
    {
        return TagTeam::class;
    }

    /**
     * Define validation rules for tag team form fields.
     *
     * Provides comprehensive validation for all tag team data including
     * uniqueness constraints, wrestler relationship validation, and
     * employment date validation through custom rules.
     *
     * Validation Requirements:
     * - Name: Required, unique, max 255 characters
     * - Signature Move: Optional, unique if provided, max 255 characters
     * - Wrestlers: Required, different wrestlers, valid IDs
     * - Employment Date: Optional, valid date, custom employment validation
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     *
     * @see CanChangeEmploymentDate For custom date validation
     * @see Rule::unique() For database uniqueness constraints
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tag_teams', 'name')->ignore($this->formModel)],
            'signature_move' => ['nullable', 'string', 'max:255', Rule::unique('tag_teams', 'signature_move')->ignore($this->formModel)],
            'wrestlerA' => ['required', 'integer', 'exists:wrestlers,id'],
            'wrestlerB' => ['required', 'integer', 'exists:wrestlers,id', 'different:wrestlerA'],
            'managers' => ['array'],
            'managers.*' => ['integer', 'exists:managers,id'],
            'employment_date' => ['nullable', 'date', new CanChangeEmploymentDate($this->formModel)],
        ];
    }

    /**
     * Get tag team-specific validation attributes.
     *
     * All standard attributes (signature_move, employment_date) are provided by
     * HasStandardValidationAttributes trait. This method handles tag team-specific
     * wrestler and manager field naming.
     *
     * @return array<string, string> Custom validation attributes for this form
     */
    protected function validationAttributes(): array
    {
        return [
            'signature_move' => 'signature move',
            'wrestlerA' => 'first wrestler',
            'wrestlerB' => 'second wrestler',
            'managers' => 'managers',
            'managers.*' => 'manager',
            'employment_date' => 'employment date',
        ];
    }
}
