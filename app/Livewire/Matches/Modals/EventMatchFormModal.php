<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsMatchTypesList;
use App\Livewire\Concerns\Data\PresentsRefereesList;
use App\Livewire\Concerns\Data\PresentsTagTeamsList;
use App\Livewire\Concerns\Data\PresentsTitlesList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\Matches\Forms\EventMatchForm;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Livewire\Form;

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
 * @extends BaseFormModal<EventMatchForm, EventMatch>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseFormModal For modal functionality and patterns
 * @see EventMatch For the underlying match model structure
 */
class EventMatchFormModal extends BaseFormModal
{
    use PresentsMatchTypesList;
    use PresentsRefereesList;
    use PresentsTagTeamsList;
    use PresentsTitlesList;
    use PresentsWrestlersList;

    /**
     * The event match form instance for data management.
     *
     * Handles all event match-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public Form $form;

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
     * @return class-string<EventMatchForm> The fully qualified class name of EventMatchForm
     */
    protected function getFormClass(): string
    {
        return EventMatchForm::class;
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
        return 'event-matches.modals.form-modal';
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

        /** @phpstan-ignore-next-line */
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

        /** @phpstan-ignore-next-line */
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
}
