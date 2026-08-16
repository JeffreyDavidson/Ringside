<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Forms;

use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\UpdateMatchAction;
use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Livewire\Base\BaseForm;
use App\Livewire\Concerns\HasStandardValidationAttributes;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Rules\Matches\CompetitorsNotDuplicated;
use App\Rules\Matches\CorrectNumberOfSides;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
 *
 * @since 1.0.0
 * @see BaseForm For base form functionality and patterns
 * @see EventMatch For the underlying event match model
 */
class CreateEditForm extends BaseForm
{
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
     * Match type for match style specification.
     *
     * Determines the rules, structure, and requirements for the wrestling
     * match (singles, tag team, ladder match, cage match, etc.).
     *
     * @var MatchType|null Match type enum
     */
    public ?MatchType $matchType = null;

    public ?int $matchStipulationId = null;

    /**
     * Array of competitors organized by sides in the match.
     *
     * Each side contains wrestlers and potentially tag teams for that side.
     * Structure: [0 => ['wrestlers' => [1, 2]], 1 => ['wrestlers' => [3, 4]]]
     *
     * @var array<int, array{wrestlers?: array<int>, tag_teams?: array<int>}> Competitors grouped by side
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

    public function resetCompetitorsFor(MatchType $matchType): void
    {
        $sideCount = $matchType->usesIndividualCompetitorSides()
            ? 1
            : ($matchType->numberOfSides() ?? 1);

        $this->competitors = array_fill(0, $sideCount, [
            'wrestlers' => [],
            'tag_teams' => [],
        ]);
    }

    /**
     * Store a new event match.
     *
     * Creates a new match with all relationships properly synced.
     *
     * @return bool True if the match was successfully created
     */
    public function store(): bool
    {
        $this->validate();

        $data = $this->toData();

        if ($this->isCreating()) {
            $event = Event::query()->findOrFail($this->eventId);
            $this->formModel = resolve(AddMatchForEventAction::class)->handle($event, $data);
        } else {
            $match = EventMatch::query()->findOrFail($this->modelId);
            $this->formModel = resolve(UpdateMatchAction::class)->handle($match, $data);
        }

        $this->modelId = $this->formModel->getKey();

        return true;
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

        // Load match type from enum property
        $this->matchType = $this->formModel->match_type;
        $this->matchStipulationId = $this->formModel->match_stipulation_id;

        $this->referees = $this->formModel->referees->pluck('id')->toArray();
        $this->titles = $this->formModel->titles->pluck('id')->toArray();
        $competitorsBySide = $this->formModel->sides()
            ->with('competitors.competitor')
            ->get()
            ->map(function (MatchSide $side): array {
                return [
                    'wrestlers' => $side->competitors
                        ->filter(fn (MatchCompetitor $competitor): bool => $competitor->competitor instanceof Wrestler)
                        ->pluck('competitor_id')
                        ->values()
                        ->all(),
                    'tag_teams' => $side->competitors
                        ->filter(fn (MatchCompetitor $competitor): bool => $competitor->competitor instanceof TagTeam)
                        ->pluck('competitor_id')
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $this->competitors = $this->matchType->usesIndividualCompetitorSides()
            ? [[
                'wrestlers' => collect($competitorsBySide)->pluck('wrestlers')->flatten()->values()->all(),
                'tag_teams' => [],
            ]]
            : $competitorsBySide;
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
            'preview' => $this->preview,
            'match_type' => $this->matchType,
            'match_stipulation_id' => $this->matchStipulationId,
        ];
    }

    private function toData(): EventMatchData
    {
        $matchType = $this->matchType;
        $sides = $matchType->usesIndividualCompetitorSides()
            ? collect($this->competitors[0]['wrestlers'] ?? [])
                ->values()
                ->mapWithKeys(fn (int $wrestlerId, int $index): array => [
                    $index + 1 => [
                        'wrestlers' => Wrestler::query()->whereKey($wrestlerId)->get()->all(),
                        'tag_teams' => [],
                    ],
                ])
            : collect($this->competitors)
                ->values()
                ->mapWithKeys(fn (array $side, int $index): array => [
                    $index + 1 => [
                        'wrestlers' => Wrestler::query()->whereKey($side['wrestlers'] ?? [])->get()->all(),
                        'tag_teams' => TagTeam::query()->whereKey($side['tag_teams'] ?? [])->get()->all(),
                    ],
                ]);

        return new EventMatchData(
            matchType: $matchType,
            referees: Referee::query()->whereKey($this->referees)->get(),
            titles: Title::query()->whereKey($this->titles)->get(),
            sides: $sides,
            preview: $this->preview,
            matchStipulation: $this->matchStipulationId === null
                ? null
                : MatchStipulation::query()->findOrFail($this->matchStipulationId),
        );
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
            'matchType' => ['required', new Enum(MatchType::class)],
            'matchStipulationId' => [
                'nullable',
                'integer',
                Rule::exists(MatchStipulation::class, 'id')->where('is_active', true),
            ],
            'preview' => ['sometimes', 'string'],
            'referees' => ['required', 'array', 'min:1'],
            'referees.*' => ['integer', 'exists:referees,id'],
            'titles' => ['sometimes', 'array'],
            'titles.*' => [
                'integer',
                'exists:titles,id',
                function (string $attribute, mixed $value, Closure $fail) {
                    $title = Title::find($value);
                    if ($title && $title->status->value !== 'active') {
                        $fail('The selected title must be active.');
                    }
                },
            ],
        ];

        // Add dynamic competitor validation based on match type
        $competitorRules = $this->getCompetitorValidationRules();
        $competitorRules['competitors'] = [
            ...$competitorRules['competitors'],
            new CorrectNumberOfSides(),
            new CompetitorsNotDuplicated(),
        ];

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
        if (! $this->matchType) {
            return [
                'competitors' => ['sometimes', 'array'],
                'competitors.*.wrestlers' => ['sometimes', 'array'],
                'competitors.*.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.*.tag_teams' => ['sometimes', 'array'],
                'competitors.*.tag_teams.*' => ['integer', 'exists:tag_teams,id'],
            ];
        }

        return $this->getValidationForMatchType($this->matchType);
    }

    /**
     * Get specific validation rules for a match type.
     *
     * @return array<string, array<string>>
     */
    private function getValidationForMatchType(MatchType $matchType): array
    {
        $matchTypeValue = $matchType->value;

        // Singles Match: 2 sides, 1 wrestler each
        if ($matchType === MatchType::Singles) {
            return [
                'competitors' => ['required', 'array', 'size:2'],
                'competitors.0.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.0.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
                'competitors.1.wrestlers' => ['required', 'array', 'size:1'],
                'competitors.1.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
            ];
        }

        // Tag Team Match: 2 sides, 2+ wrestlers or tag teams
        if (in_array($matchType, [MatchType::TagTeam, MatchType::SixManTagTeam, MatchType::EightManTagTeam, MatchType::TenManTagTeam, MatchType::TornadoTagTeam], true)) {
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
        if (in_array($matchType, [MatchType::TripleThreat, MatchType::Triangle], true)) {
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
        if ($matchType === MatchType::Fatal4Way) {
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

        if ($matchType->usesIndividualCompetitorSides()) {
            $maximumCompetitors = $matchType->getMaximumCompetitors();
            $wrestlerRules = ['required', 'array', 'min:'.$matchType->getMinimumCompetitors()];

            if ($maximumCompetitors !== null) {
                $wrestlerRules[] = 'max:'.$maximumCompetitors;
            }

            return [
                'competitors' => ['required', 'array', 'size:1'],
                'competitors.0.wrestlers' => $wrestlerRules,
                'competitors.0.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
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
            'matchType' => 'match type',
            'matchStipulationId' => 'match stipulation',
            'competitors' => 'competitors',
            'referees' => 'referees',
            'titles' => 'championship titles',
        ];
    }
}
