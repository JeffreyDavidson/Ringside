<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsMatchTypesList;
use App\Livewire\Concerns\Data\PresentsRefereesList;
use App\Livewire\Concerns\Data\PresentsTagTeamsList;
use App\Livewire\Concerns\Data\PresentsTitlesList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Log;

/**
 * Livewire modal component for wrestling match management within events.
 *
 * Manages the creation and editing of individual wrestling matches that occur
 * during events. Handles complex relationships between wrestlers, referees,
 * titles, match types, and promotional content. Supports dynamic view rendering
 * based on match type requirements.
 *
 * Key Features:
 * - Modal-based event match form interface
 * - Match type and competitor assignment
 * - Referee and title assignment
 * - Dynamic view rendering based on match type
 * - Wrestling match data generation for testing
 *
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseFormModal For modal functionality and patterns
 * @see EventMatch For the underlying match model structure
 */
class FormModal extends BaseFormModal
{
    use PresentsMatchTypesList;
    use PresentsRefereesList;
    use PresentsTagTeamsList;
    use PresentsTitlesList;
    use PresentsWrestlersList;

    /**
     * Event identifier for match association.
     *
     * Required context for all matches since they cannot exist without an event.
     * Comes from route model binding in the parent component.
     *
     * @var int Event database ID
     */
    public int $eventId;

    /**
     * String name to render view for each match type.
     *
     * Determines which Blade template to use for rendering match-specific
     * form fields based on the selected match type (singles, tag team, etc.).
     *
     * @var string View template identifier for dynamic rendering
     */
    public string $subViewToUse;

    /**
     * Get the form class that handles event match data validation and processing.
     *
     * @return class-string<CreateEditForm> The fully qualified class name of CreateEditForm
     */
    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    /**
     * Get the model class that represents event match entities.
     *
     * @return class-string<EventMatch> The fully qualified class name of EventMatch model
     */
    protected function getModelClass(): string
    {
        return EventMatch::class;
    }

    /**
     * Get the Blade view path for rendering the event match form modal.
     *
     * @return string The view path relative to resources/views
     */
    protected function getModalPath(): string
    {
        return 'livewire.matches.modals.form-modal';
    }

    /**
     * Generate dummy data fields for event match testing and development.
     *
     * Returns field generators for event match data including match types,
     * competitor assignments, referee assignments, title stakes, and promotional content.
     *
     * @return array<string, callable(): mixed> Array mapping field names to generators
     */
    protected function getDummyDataFields(): array
    {
        return [
            'matchTypeId' => fn () => $this->getRandomMatchTypeId(),
            'referees' => fn () => $this->generateRefereeAssignments(),
            'titles' => fn () => $this->generateTitleAssignments(),
            'wrestlers' => fn () => $this->generateWrestlerAssignments(),
            'preview' => fn () => $this->generateMatchPreview(),
        ];
    }

    /**
     * Generate random event match data for testing and development.
     *
     * Populates the form with realistic wrestling match data including
     * match types, competitor assignments, referee assignments, title
     * stakes, and promotional content.
     */
    public function generateRandomData(): void
    {
        $this->form->fill([
            'matchTypeId' => $this->getRandomMatchTypeId(),
            'referees' => $this->generateRefereeAssignments(),
            'titles' => $this->generateTitleAssignments(),
            'wrestlers' => $this->generateWrestlerAssignments(),
            'preview' => $this->generateMatchPreview(),
        ]);
    }

    /**
     * Get a random match type ID for testing.
     *
     * Creates match type assignments for various wrestling match styles
     * including singles, tag team, ladder matches, cage matches, etc.
     *
     * @return int A random match type ID
     */
    protected function getRandomMatchTypeId(): int
    {
        /** @phpstan-ignore-next-line */
        return MatchType::inRandomOrder()->first()?->id ?? MatchType::factory()->create()->id;
    }

    /**
     * Generate referee assignments for the match.
     *
     * Creates referee assignments appropriate for different match types.
     * Most matches have one referee, but special matches may require
     * additional officials.
     *
     * @return array<int> Array of referee IDs
     */
    protected function generateRefereeAssignments(): array
    {
        // Most matches have 1 referee, some special matches might have 2
        $refereeCount = fake()->randomFloat(null, 0, 1) < 0.9 ? 1 : 2;

        return Referee::factory()->count($refereeCount)->create()->pluck('id')->toArray();
    }

    /**
     * Generate title assignments for championship matches.
     *
     * Creates title stakes for matches, with some matches being for
     * championships and others being non-title matches.
     *
     * @return array<int> Array of title IDs (empty for non-title matches)
     */
    protected function generateTitleAssignments(): array
    {
        // 30% chance of being a championship match
        if (fake()->randomFloat(null, 0, 1) < 0.3) {
            /** @phpstan-ignore-next-line */
            return [Title::inRandomOrder()->first()?->id ?? Title::factory()->create()->id];
        }

        return []; // Non-title match
    }

    /**
     * Generate wrestler assignments for the match.
     *
     * Creates wrestler assignments appropriate for different match types.
     * Singles matches get 2 wrestlers, tag team matches get 4, etc.
     *
     * @return array<int> Array of wrestler IDs
     */
    protected function generateWrestlerAssignments(): array
    {
        // For now, generate 2-4 wrestlers (singles to tag team)
        $wrestlerCount = fake()->numberBetween(2, 4);

        return Wrestler::factory()->count($wrestlerCount)->create()->pluck('id')->toArray();
    }

    /**
     * Generate promotional preview content for wrestling matches.
     *
     * Creates realistic promotional text that would be used to advertise
     * wrestling matches, including storyline elements, competitor highlights,
     * and match stipulations.
     *
     * @return string Generated match preview text
     */
    protected function generateMatchPreview(): string
    {
        $matchIntros = [
            'In what promises to be an explosive encounter',
            'Two wrestling titans collide when',
            'The stage is set for an epic showdown as',
            'Tensions reach a boiling point in this highly anticipated match featuring',
            'Get ready for non-stop action when',
            'The rivalry intensifies as',
        ];

        $matchElements = [
            'championship gold hangs in the balance',
            'personal vendettas will be settled',
            'only one competitor can emerge victorious',
            'careers and reputations are on the line',
            'months of buildup culminate in this decisive battle',
            'the wrestling world will witness something special',
        ];

        $callsToAction = [
            "Don't miss this incredible matchup!",
            "You won't want to blink during this one!",
            'This match could steal the entire show!',
            'Witness wrestling at its absolute finest!',
            'The action starts when the bell rings!',
            'This is what wrestling is all about!',
        ];

        return fake()->randomElement($matchIntros).' '.
               fake()->randomElement($matchElements).'. '.
               fake()->randomElement($callsToAction);
    }

    /**
     * Component mount lifecycle - properly initialize eventId before form creation.
     *
     * @param  mixed  $modelId  Optional model ID for editing (Livewire standard)
     */
    public function mount(mixed $modelId = null): void
    {
        parent::mount($modelId);

        // Set eventId on form - this should always happen since eventId is required context
        if ($this->eventId > 0 && $this->form) {
            $this->form->eventId = $this->eventId;
        } elseif ($this->form) {
            // Log warning if eventId is not properly set (development aid)
            Log::warning('Matches FormModal: eventId not properly initialized', [
                'eventId' => $this->eventId,
                'component' => static::class,
            ]);
        }
    }

    public function openModal(mixed $modelId = null): void
    {
        // Check authorization before opening modal
        if ($modelId !== null) {
            // Editing existing match - check update permission
            Gate::authorize('update', EventMatch::class);
        } else {
            // Creating new match - check create permission
            Gate::authorize('create', EventMatch::class);
        }

        parent::openModal($modelId);
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit Match';
        }

        return 'Create Match';
    }

    public function submitForm(): bool
    {
        // Store whether we're creating or updating before the form submission
        $isCreating = $this->form->isCreating();

        $result = parent::submitForm();

        if ($result) {
            // Dispatch the appropriate event based on whether we created or updated
            if ($isCreating) {
                $this->dispatch('matchCreated');
            } else {
                $this->dispatch('matchUpdated');
            }

            // Reset the form after successful submission
            $this->form->reset();
        }

        return $result;
    }

    /**
     * Handle match type selection changes for dynamic UI updates.
     *
     * When the match type changes, we need to:
     * 1. Clear incompatible competitor data
     * 2. Initialize the correct competitor structure
     * 3. Reset validation state
     */
    public function updatedFormMatchTypeId($value): void
    {
        if (! $value) {
            return;
        }

        // Clear existing competitor data when match type changes
        $this->form->competitors = [];

        // Initialize competitor structure based on match type
        $this->initializeCompetitorStructure($value);
    }

    /**
     * Get the currently selected match type model.
     */
    public function getSelectedMatchType(): ?MatchType
    {
        if (! $this->form->matchTypeId) {
            return null;
        }

        return MatchType::find($this->form->matchTypeId);
    }

    /**
     * Check if the current match type allows wrestlers.
     */
    public function getMatchTypeAllowsWrestlersProperty(): bool
    {
        $matchType = $this->getSelectedMatchType();

        return $matchType ? $matchType->allowsWrestlers() : true;
    }

    /**
     * Check if the current match type allows tag teams.
     */
    public function getMatchTypeAllowsTagTeamsProperty(): bool
    {
        $matchType = $this->getSelectedMatchType();

        return $matchType ? $matchType->allowsTagTeams() : false;
    }

    /**
     * Get the number of sides required for the current match type.
     */
    public function getNumberOfSidesProperty(): int
    {
        $matchType = $this->getSelectedMatchType();

        return $matchType ? $matchType->getMinimumCompetitors() : 2;
    }

    /**
     * Get the match type name for template logic.
     */
    public function getMatchTypeNameProperty(): string
    {
        $matchType = $this->getSelectedMatchType();

        return $matchType ? mb_strtolower($matchType->name) : '';
    }

    /**
     * Initialize the competitor structure based on match type.
     */
    private function initializeCompetitorStructure(int $matchTypeId): void
    {
        $matchType = MatchType::find($matchTypeId);

        if (! $matchType) {
            return;
        }

        $numberOfSides = $matchType->getMinimumCompetitors();
        $competitors = [];

        $matchTypeName = mb_strtolower($matchType->name);

        // Initialize competitor structure based on match type specifics
        if (str_contains($matchTypeName, 'battle') || str_contains($matchTypeName, 'rumble') || str_contains($matchTypeName, 'royal')) {
            // Battle Royal: Single array for multiple wrestlers
            $competitors[0] = [
                'wrestlers' => [],
                'tag_teams' => [],
            ];
        } else {
            // Other matches: Initialize empty competitor structure for each side
            for ($i = 0; $i < $numberOfSides; $i++) {
                $competitors[$i] = [
                    'wrestlers' => [],
                    'tag_teams' => [],
                ];
            }
        }

        $this->form->competitors = $competitors;
    }

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.matches.modals.form-modal');
    }
}
