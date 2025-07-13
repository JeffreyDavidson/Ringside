<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Forms;

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\GeneratesDummyData;
use App\Livewire\Concerns\HasStandardValidationAttributes;
use App\Models\Matches\EventMatch;
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
 * @extends LivewireBaseForm<EventMatchForm, EventMatch>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality and patterns
 * @see EventMatch For the underlying event match model
 *
 * @property string $preview Match promotional preview content
 * @property int $matchTypeId Match type identifier
 * @property array<int> $competitors Array of competitor IDs (wrestlers/tag teams)
 * @property array<int> $referees Array of referee IDs for match officials
 * @property array<int> $titles Array of title IDs at stake in the match
 */
class EventMatchForm extends LivewireBaseForm
{
    use GeneratesDummyData;

    /**
     * The model instance being edited, or null for new event match creation.
     *
     * @var EventMatch|null Current event match model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Match promotional preview content for marketing purposes.
     *
     * Used in promotional materials, match cards, and event advertising
     * to generate interest and provide match context to fans.
     *
     * @var string Marketing preview text for the match
     */
    public string $preview = '';

    /**
     * Match type identifier for match style specification.
     *
     * Determines the rules, structure, and requirements for the wrestling
     * match (singles, tag team, ladder match, cage match, etc.).
     *
     * @var int Match type database ID
     */
    public int $matchTypeId = 0;

    /**
     * Array of competitor IDs participating in the match.
     *
     * Contains wrestler or tag team IDs depending on match type requirements.
     * Used for match participant assignment and validation.
     *
     * @var array<int> Competitor database IDs
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

        // Create new competitor records
        foreach ($this->competitors as $wrestlerId) {
            $this->formModel->competitors()->create([
                'wrestler_id' => $wrestlerId,
                // Add any other required fields for the EventMatchCompetitor model
            ]);
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
            'preview' => $this->preview,
            'match_type_id' => $this->matchTypeId,
        ];
        // Note: relationships (competitors, referees, titles) are handled
        // separately through the relationship synchronization system
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
        return [
            'matchTypeId' => ['required', 'integer', 'exists:match_types,id'],
            'preview' => ['required', 'string'],
            'competitors' => ['required', 'array', 'min:2'],
            'competitors.*' => ['integer', 'exists:wrestlers,id'],
            'referees' => ['required', 'array', 'min:1'],
            'referees.*' => ['integer', 'exists:referees,id'],
            'titles' => ['sometimes', 'array'],
            'titles.*' => ['integer', 'exists:titles,id'],
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
