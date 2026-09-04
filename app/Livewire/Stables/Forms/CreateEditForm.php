<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Forms;

use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Livewire\Base\BaseForm;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Shared\CanChangeDebutDate;
use App\Rules\Stables\CanJoinStable;
use App\Rules\Stables\HasMinimumMembers;
use App\Rules\Wrestlers\IsNotInjured;
use App\Rules\Wrestlers\NotRepresentedBySelectedTagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use LogicException;

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
 * @extends BaseForm<Stable>
 *
 * @see BaseForm For base form functionality and patterns
 * @see StableData For typed Action input
 * @see CanChangeDebutDate For custom activation validation
 *
 * @property string $name Stable's official name for storylines and promotion
 * @property string|null $start_date Stable activation start date
 */
class CreateEditForm extends BaseForm
{
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
    public ?string $started_at = null;

    /**
     * Stable deactivation end date for faction history tracking.
     *
     * Tracks when a wrestling stable becomes inactive or is disbanded.
     * Used for completing activity periods.
     *
     * @var string|null Stable deactivation end date (string to prevent auto-casting)
     */
    public ?string $ended_at = null;

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
     * @see StableData::$start_date For activity period input
     */
    public function loadExtraData(): void
    {
        if (! $this->formModel instanceof Stable) {
            return;
        }

        $this->started_at = $this->formModel->firstActivityPeriod?->started_at?->toDateString();
        $this->ended_at = $this->formModel->firstActivityPeriod?->ended_at?->toDateString();
        $this->wrestlers = $this->formModel->currentWrestlers->modelKeys();
        $this->tag_teams = $this->formModel->currentTagTeams->modelKeys();
    }

    public function toData(): StableData
    {
        $members = $this->selectedMembers();

        return new StableData(
            name: $this->name,
            start_date: $this->started_at ? Carbon::parse($this->started_at) : null,
            members: $members,
            end_date: $this->ended_at ? Carbon::parse($this->ended_at) : null,
        );
    }

    public function stable(): Stable
    {
        return Stable::query()->findOrFail($this->modelId);
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
    /**
     * Get the model class for stable form operations.
     *
     * Specifies the Stable model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<Stable> The Stable model class
     */
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
        $stableStartDate = $this->parseStartDate();
        $members = $this->selectedMembers();
        $stable = $this->isEditing() ? $this->stable() : null;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('stables', 'name')->ignore($this->modelId)->withoutTrashed(),
            ],
            'started_at' => [
                'nullable',
                'date',
                new CanChangeDebutDate($stable),
                new HasMinimumMembers(
                    $members->wrestlers ?? collect(),
                    $members->tagTeams ?? collect(),
                ),
            ],
            'ended_at' => ['nullable', 'date'],
            'wrestlers' => ['nullable', 'array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                'exists:wrestlers,id',
                new CanJoinStable(Wrestler::class, $this->stableId(), $stableStartDate),
                new IsNotInjured(),
                new NotRepresentedBySelectedTagTeam(collect($this->tag_teams)),
            ],
            'tag_teams' => ['nullable', 'array'],
            'tag_teams.*' => [
                'bail',
                'integer',
                'exists:tag_teams,id',
                new CanJoinStable(TagTeam::class, $this->stableId(), $stableStartDate),
            ],
        ];

        if (! in_array($this->started_at, [null, '', '0'], true) && ! in_array($this->ended_at, [null, '', '0'], true)) {
            $rules['ended_at'][] = 'after:started_at';
        }

        return $rules;
    }

    /**
     * Load the members selected by the form into the shared stable data object.
     */
    private function selectedMembers(): StableMembershipData
    {
        return new StableMembershipData(
            wrestlers: Wrestler::query()->whereKey($this->wrestlers)->get(),
            tagTeams: TagTeam::query()->whereKey($this->tag_teams)->get(),
        );
    }

    private function parseStartDate(): ?Carbon
    {
        if ($this->started_at === null || strtotime($this->started_at) === false) {
            return null;
        }

        return Carbon::parse($this->started_at);
    }

    private function stableId(): ?int
    {
        if ($this->modelId === null || is_int($this->modelId)) {
            return $this->modelId;
        }

        if (ctype_digit($this->modelId)) {
            return (int) $this->modelId;
        }

        throw new LogicException('Stable forms require integer model keys.');
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
