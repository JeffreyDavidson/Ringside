<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Forms;

use App\Livewire\Base\BaseForm;
use App\Livewire\Concerns\GeneratesDummyData;
use App\Livewire\Concerns\HasStandardValidationAttributes;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;

/**
 * Livewire form component for managing event match creation and editing.
 *
 * This form handles the complete lifecycle of wrestling match management within
 * events, including competitor assignments, referee assignments, title stakes,
 * and match type specifications. Provides specialized validation for complex
 * wrestling match requirements and relationship management.
 *
 * Key Responsibilities:
 * - Event match information management (preview, match type)
 * - Competitor relationship management (wrestlers, tag teams)
 * - Official assignments (referees)
 * - Championship title stakes and implications
 * - Match-specific validation and business rules
 *
 * @extends BaseForm<CreateEditForm, EventMatch>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseForm For base form functionality and patterns
 * @see EventMatch For the underlying event match model
 *
 * @property string $preview Match promotional preview content
 * @property int $matchTypeId Match type identifier
 * @property array<int> $competitors Array of competitor IDs (wrestlers/tag teams)
 * @property array<int> $referees Array of referee IDs for match officials
 * @property array<int> $titles Array of title IDs at stake in the match
 */
class CreateEditForm extends BaseForm
{
    use GeneratesDummyData;

    /**
     * The model instance being edited, or null for new event match creation.
     *
     * @var EventMatch|null Current event match model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Event identifier for match association.
     *
     * Links the match to a specific wrestling event where it will take place.
     * This is always required since matches cannot exist without an event.
     * Provided by route model binding, not user input.
     *
     * Default value of 0 indicates uninitialized - will be set during component mount.
     *
     * @var int Event database ID
     */
    public int $eventId = 0;

    /**
     * Match promotional preview content for marketing purposes.
     *
     * Used in promotional materials, match cards, and event advertising
     * to generate interest and provide match context to fans.
     *
     * @var string Marketing preview text for the match
     */
    public ?string $preview = '';

    /**
     * Match type identifier for match style specification.
     *
     * Determines the rules, structure, and requirements for the wrestling
     * match (singles, tag team, ladder match, cage match, etc.).
     *
     * @var int|null Match type database ID
     */
    public ?int $matchTypeId = null;

    /**
     * Array of competitors organized by sides in the match.
     *
     * Each side contains wrestlers and potentially tag teams for that side.
     * Structure: [0 => ['wrestlers' => [1, 2]], 1 => ['wrestlers' => [3, 4]]]
     *
     * @var array<int, array{wrestlers: array<int>}> Competitors grouped by side
     */
    public array $competitors = [];

    /**
     * Array of referee IDs assigned to officiate the match.
     *
     * Most matches have one referee, but special matches may require
     * additional officials for proper oversight.
     *
     * @var array<int> Referee database IDs
     */
    public array $referees = [];

    /**
     * Array of title IDs at stake in the match.
     *
     * Empty array for non-title matches, populated for championship
     * matches with title implications.
     *
     * @var array<int> Title database IDs
     */
    public array $titles = [];

    /**
     * Store a new event match.
     *
     * Creates a new match with all relationships properly synced.
     *
     * @return bool True if the match was successfully created
     */
    public function store(): bool
    {
        $result = parent::store();

        if ($result) {
            $this->syncRelationships();
        }

        return $result;
    }

    /**
     * Load additional data when editing existing event match records.
     *
     * Handles complex relationship data loading for edit operations,
     * retrieving competitor assignments, referee assignments, and title
     * stakes from the event match's relationship system.
     *
     * Relationship Loading:
     * - Loads match type from relationship
     * - Loads competitor IDs from many-to-many relationships
     * - Loads referee assignments
     * - Loads title stakes for championship matches
     */
    public function loadExtraData(): void
    {
        // Only process if we have an event match model
        if (! $this->formModel instanceof EventMatch) {
            return;
        }

        // Load match type from relationship
        $this->matchTypeId = $this->formModel->matchType?->getKey() ?? 0;

        // Load competitor IDs from relationships
        $this->referees = $this->formModel->referees->pluck('id')->toArray();
        $this->titles = $this->formModel->titles->pluck('id')->toArray();
        $this->competitors = $this->formModel->competitors->pluck('wrestler_id')->toArray(); // Assuming wrestler_id is the foreign key
    }

    /**
     * Synchronize all event match relationships.
     *
     * Updates the relationships for referees, titles, and competitors
     * to match the current form state. Handles both many-to-many
     * and one-to-many relationships appropriately.
     */
    private function syncRelationships(): void
    {
        if (! $this->formModel instanceof EventMatch) {
            return;
        }

        // Sync many-to-many relationships (assuming referees and titles are BelongsToMany)
        $this->formModel->referees()->sync($this->referees);
        $this->formModel->titles()->sync($this->titles);

        // Handle competitors - assuming it's a HasMany relationship
        $this->syncCompetitors();
    }

    /**
     * Sync competitors for HasMany relationship.
     *
     * Removes existing competitors and creates new ones based on
     * the current form state.
     */
    private function syncCompetitors(): void
    {
        if (! $this->formModel instanceof EventMatch) {
            return;
        }

        // Delete existing competitors
        $this->formModel->competitors()->delete();

        // Create new competitor records using the side-based structure
        foreach ($this->competitors as $sideNumber => $sideCompetitors) {
            // Handle wrestlers for this side
            if (isset($sideCompetitors['wrestlers'])) {
                foreach ($sideCompetitors['wrestlers'] as $wrestlerId) {
                    $this->formModel->competitors()->create([
                        'competitor_type' => Wrestler::class,
                        'competitor_id' => $wrestlerId,
                        'side_number' => $sideNumber,
                    ]);
                }
            }

            // Handle tag teams for this side (when implemented)
            if (isset($sideCompetitors['tag_teams'])) {
                foreach ($sideCompetitors['tag_teams'] as $tagTeamId) {
                    $this->formModel->competitors()->create([
                        'competitor_type' => TagTeam::class,
                        'competitor_id' => $tagTeamId,
                        'side_number' => $sideNumber,
                    ]);
                }
            }
        }
    }

    /**
     * Prepare event match data for model storage.
     *
     * Transforms form fields into model-compatible data structure.
     * Excludes relationship data which is handled separately through
     * the relationship synchronization system.
     *
     * @return array<string, mixed> Model data ready for persistence
     */
    protected function getModelData(): array
    {
        return [
            'event_id' => $this->eventId,
            'match_number' => $this->getNextMatchNumber(),
            'preview' => $this->preview,
            'match_type_id' => $this->matchTypeId,
        ];
        // Note: relationships (competitors, referees, titles) are handled
        // separately through the relationship synchronization system
    }

    /**
     * Get the next match number for the event.
     *
     * Calculates the next sequential match number based on existing matches
     * for the specified event. Match numbers start from 1.
     *
     * @return int Next match number (1-based)
     */
    private function getNextMatchNumber(): int
    {
        $maxMatchNumber = EventMatch::where('event_id', $this->eventId)->max('match_number');

        return ($maxMatchNumber ?? 0) + 1;
    }

    /**
     * Get the model class for event match form operations.
     *
     * Specifies the EventMatch model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<EventMatch> The EventMatch model class
     */
    protected function getModelClass(): string
    {
        return EventMatch::class;
    }

    /**
     * Define validation rules for event match form fields.
     *
     * Provides comprehensive validation for all event match data including
     * match type requirements, competitor assignments, referee assignments,
     * and promotional content validation.
     *
     * Validation Requirements:
     * - Preview: Required promotional content
     * - Match Type: Required, must exist in match_types table
     * - Competitors: Required array, minimum participants based on match type
     * - Referees: Required array, at least one referee
     * - Titles: Optional array for championship matches
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     */
    protected function rules(): array
    {
        $baseRules = [
            // eventId removed - it's context from route model binding, not user input
            'matchTypeId' => ['required', 'integer', 'min:1', 'exists:match_types,id'],
            'preview' => ['sometimes', 'string'],
            'referees' => ['sometimes', 'array'],
            'referees.*' => ['integer', 'exists:referees,id'],
            'titles' => ['sometimes', 'array'],
            'titles.*' => [
                'integer',
                'exists:titles,id',
                function ($attribute, $value, $fail) {
                    $title = Title::find($value);
                    if ($title && $title->status->value !== 'active') {
                        $fail('The selected title must be active.');
                    }
                },
            ],
        ];

        // Add dynamic competitor validation based on match type
        $competitorRules = $this->getCompetitorValidationRules();

        return array_merge($baseRules, $competitorRules);
    }

    /**
     * Get validation rules for competitors based on the match type.
     *
     * @return array<string, array<string>>
     */
    private function getCompetitorValidationRules(): array
    {
        // If no match type is selected yet, use basic validation
        if (! $this->matchTypeId) {
            return [
                'competitors' => ['sometimes', 'array'],
                'competitors.*.wrestlers' => ['sometimes', 'array'],
                'competitors.*.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.*.tag_teams' => ['sometimes', 'array'],
                'competitors.*.tag_teams.*' => ['integer', 'exists:tag_teams,id'],
            ];
        }

        // Get the match type from database to determine validation rules
        $matchType = MatchType::find($this->matchTypeId);

        if (! $matchType) {
            return [
                'competitors' => ['sometimes', 'array'],
            ];
        }

        return $this->getValidationForMatchType($matchType);
    }

    /**
     * Get specific validation rules for a match type.
     *
     * @param  MatchType  $matchType
     * @return array<string, array<string>>
     */
    private function getValidationForMatchType($matchType): array
    {
        $matchTypeName = mb_strtolower($matchType->name);

        // Singles Match: 2 sides, 1 wrestler each
        if (str_contains($matchTypeName, 'singles')) {
            return [
                'competitors' => ['required', 'array', 'size:2'],
                'competitors.0.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.0.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.1.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.1.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
            ];
        }

        // Tag Team Match: 2 sides, 2+ wrestlers or tag teams
        if (str_contains($matchTypeName, 'tag') || str_contains($matchTypeName, 'team')) {
            return [
                'competitors' => ['required', 'array', 'size:2'],
                'competitors.0' => ['required', 'array'],
                'competitors.0.wrestlers' => ['sometimes', 'array', 'min:2'],
                'competitors.0.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.0.tag_teams' => ['sometimes', 'array', 'min:1'],
                'competitors.0.tag_teams.*' => ['integer', 'exists:tag_teams,id'],
                'competitors.1' => ['required', 'array'],
                'competitors.1.wrestlers' => ['sometimes', 'array', 'min:2'],
                'competitors.1.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.1.tag_teams' => ['sometimes', 'array', 'min:1'],
                'competitors.1.tag_teams.*' => ['integer', 'exists:tag_teams,id'],
            ];
        }

        // Triple Threat: 3 sides, 1 wrestler each
        if (str_contains($matchTypeName, 'triple') || str_contains($matchTypeName, 'three')) {
            return [
                'competitors' => ['required', 'array', 'size:3'],
                'competitors.0.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.0.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.1.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.1.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.2.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.2.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
            ];
        }

        // Fatal Four Way: 4 sides, 1 wrestler each
        if (str_contains($matchTypeName, 'fatal') || str_contains($matchTypeName, 'four')) {
            return [
                'competitors' => ['required', 'array', 'size:4'],
                'competitors.0.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.0.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.1.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.1.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.2.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.2.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.3.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.3.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
            ];
        }

        // Battle Royal / Rumble: Multiple sides, 1 wrestler each
        if (str_contains($matchTypeName, 'battle') || str_contains($matchTypeName, 'rumble') || str_contains($matchTypeName, 'royal')) {
            return [
                'competitors' => ['required', 'array', 'min:6'], // Minimum 6 for battle royal
                'competitors.*.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.*.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
            ];
        }

        // Default: Basic validation for unknown match types
        return [
            'competitors' => ['required', 'array', 'min:2'],
            'competitors.*.wrestlers' => ['sometimes', 'array'],
            'competitors.*.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
            'competitors.*.tag_teams' => ['sometimes', 'array'],
            'competitors.*.tag_teams.*' => ['integer', 'exists:tag_teams,id'],
        ];
    }

    /**
     * Get custom validation attributes specific to event match forms.
     *
     * Provides event match-specific field name mappings for validation
     * error messages, extending the standard validation attributes from
     * the HasStandardValidationAttributes trait.
     *
     * @return array<string, string> Field name mappings for validation messages
     */
    protected function getCustomValidationAttributes(): array
    {
        return [
            'preview' => 'match preview',
            'matchTypeId' => 'match type',
            'competitors' => 'competitors',
            'referees' => 'referees',
            'titles' => 'championship titles',
        ];
    }

    /**
     * Get dummy data field definitions for event match forms.
     *
     * Provides realistic fake data generators for development and testing
     * purposes, allowing quick population of match forms with wrestling-
     * appropriate dummy data.
     *
     * @return array<string, callable|mixed> Array mapping field names to generators
     */
    protected function getDummyDataFields(): array
    {
        return [
            'preview' => fn () => fake()->paragraph(2).' This epic showdown promises to deliver non-stop action!',
            'matchTypeId' => fn () => fake()->numberBetween(1, 10), // Assuming match types 1-10 exist
            'competitors' => fn () => fake()->randomElements(range(1, 50), fake()->numberBetween(2, 6)), // 2-6 wrestlers
            'referees' => fn () => [fake()->numberBetween(1, 20)], // Single referee
            'titles' => fn () => fake()->boolean(0.3) ? fake()->randomElements(range(1, 15), fake()->numberBetween(1, 2)) : [], // 30% chance of title match
        ];
    }
}
