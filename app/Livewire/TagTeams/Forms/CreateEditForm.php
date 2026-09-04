<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Forms;

use App\Data\TagTeams\TagTeamData;
use App\Livewire\Base\BaseForm;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Shared\CanChangeEmploymentDate;
use App\Rules\Wrestlers\CanJoinTagTeam;
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
 * @extends BaseForm<TagTeam>
 *
 * @see BaseForm For base form functionality and patterns
 * @see CanChangeEmploymentDate For custom validation rules
 *
 * @property string $name Tag team's official name
 * @property string|null $signature_move Tag team's finishing move or signature
 * @property int|null $wrestlerA First wrestler ID in the tag team
 * @property int|null $wrestlerB Second wrestler ID in the tag team
 * @property array<int, int> $managers Array of manager IDs assigned to the tag team
 * @property string|null $employment_date Employment start date (string to prevent auto-casting)
 */
class CreateEditForm extends BaseForm
{
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
     * String to prevent Livewire from automatically casting the submitted date.
     *
     * @var string|null Employment start date (string to prevent auto-casting issues)
     */
    public ?string $employment_date = '';

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
     */
    public function loadExtraData(): void
    {
        if (! $this->formModel instanceof TagTeam) {
            return;
        }

        if ($this->formModel->employments()->exists()) {
            $this->employment_date = $this->formModel->firstEmployment?->started_at?->toDateString();
        }

        $currentWrestlers = $this->formModel->currentWrestlers;
        $this->wrestlerA = $currentWrestlers->first()?->id;
        $this->wrestlerB = $currentWrestlers->skip(1)->first()?->id;

        $this->managers = $this->formModel->currentManagers
            ->map(fn (Manager $manager): int => $manager->id)
            ->all();
    }

    public function toData(): TagTeamData
    {
        return new TagTeamData(
            name: $this->name,
            signature_move: $this->signature_move ?: null,
            employment_date: $this->employment_date ? Carbon::parse($this->employment_date) : null,
            wrestlerA: Wrestler::query()->findOrFail($this->wrestlerA),
            wrestlerB: Wrestler::query()->findOrFail($this->wrestlerB),
            managers: Manager::query()->whereKey($this->managers)->get(),
        );
    }

    public function tagTeam(): TagTeam
    {
        return TagTeam::query()->findOrFail($this->modelId);
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
    /**
     * Get the model class for tag team form operations.
     *
     * Specifies the TagTeam model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<TagTeam> The TagTeam model class
     */
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
        $tagTeam = $this->isEditing() ? $this->tagTeam() : null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tag_teams', 'name')->ignore($this->modelId)],
            'signature_move' => ['nullable', 'string', 'max:255', Rule::unique('tag_teams', 'signature_move')->ignore($this->modelId)],
            'wrestlerA' => ['bail', 'required', 'integer', 'exists:wrestlers,id', new CanJoinTagTeam($this->modelId)],
            'wrestlerB' => ['bail', 'required', 'integer', 'exists:wrestlers,id', 'different:wrestlerA', new CanJoinTagTeam($this->modelId)],
            'managers' => ['array'],
            'managers.*' => ['integer', 'exists:managers,id'],
            'employment_date' => ['nullable', 'date', new CanChangeEmploymentDate($tagTeam)],
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
