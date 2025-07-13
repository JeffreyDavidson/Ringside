<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Events\Forms\EventForm;
use App\Models\Events\Event;
use App\Models\Shared\Venue;
use Livewire\Attributes\Computed;

/**
 * Livewire modal component for wrestling event management.
 *
 * Manages the creation and editing of wrestling events including pay-per-views,
 * television shows, house shows, and special events. Handles event scheduling,
 * venue relationships, and promotional content generation.
 *
 * Key Features:
 * - Modal-based event form interface
 * - Wrestling event name generation
 * - Event scheduling and venue assignment
 * - Promotional content generation
 * - Integration with event management workflows
 *
 * @extends BaseFormModal<EventForm, Event>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseFormModal For modal functionality and patterns
 * @see Event For the underlying event model structure
 */
class EventFormModal extends BaseFormModal
{
    /**
     * The event form instance for data management.
     *
     * Handles all event-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public EventForm $form;

    /**
     * Get list of venues for form dropdown.
     */
    #[Computed]
    public function getVenues(): \Illuminate\Database\Eloquent\Collection
    {
        return Venue::orderBy('name')->get();
    }

    /**
     * Get the form class that handles event data validation and processing.
     *
     * @return class-string<EventForm> The fully qualified class name of EventForm
     */
    protected function getFormClass(): string
    {
        return EventForm::class;
    }

    /**
     * Get the model class that represents event entities.
     *
     * @return class-string<Event> The fully qualified class name of Event model
     */
    protected function getModelClass(): string
    {
        return Event::class;
    }

    /**
     * Get the Blade view path for rendering the event form modal.
     *
     * @return string The view path relative to resources/views
     */
    protected function getModalPath(): string
    {
        return 'events.modals.form-modal';
    }

    /**
     * Override mount to ensure proper form synchronization.
     */
    public function mount(mixed $modelId = null): void
    {
        parent::mount($modelId);

        // Additional synchronization for EventForm
        if ($modelId && $this->model) {
            $this->form->setModel($this->model);
        }
    }

    /**
     * Generate dummy data fields for wrestling event testing and development.
     *
     * Returns field generators for event data including wrestling-appropriate
     * names, scheduling, venue assignments, and promotional content.
     *
     * @return array<string, callable(): mixed> Array mapping field names to generators
     */
    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => $this->generateEventName(),
            'date' => fn () => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            /** @phpstan-ignore-next-line */
            'venue' => fn () => Venue::inRandomOrder()->first()?->id ?? Venue::factory()->create()->id,
            'preview' => fn () => $this->generateEventPreview(),
        ];
    }

    /**
     * Generate random event data for testing and development.
     *
     * Populates the form with realistic wrestling event data including names,
     * scheduling, venue assignments, and promotional content. Generates data
     * appropriate for various types of wrestling events.
     */
    public function generateRandomData(): void
    {
        // Generate venue assignment
        /** @phpstan-ignore-next-line */
        $venueId = Venue::inRandomOrder()->first()?->id ?? Venue::factory()->create()->id;

        $this->form->fill([
            'name' => $this->generateEventName(),
            'date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'venue' => $venueId,
            'preview' => $this->generateEventPreview(),
        ]);
    }

    /**
     * Generate a wrestling event name locally for this form.
     */
    private function generateEventName(): string
    {
        $famousEvents = [
            'WrestleMania', 'SummerSlam', 'Royal Rumble', 'Survivor Series',
            'Money in the Bank', 'Hell in a Cell', 'TLC', 'Extreme Rules',
            'Fastlane', 'Elimination Chamber', 'Backlash', 'Payback',
        ];

        $weeklyShows = [
            'Monday Night Raw', 'SmackDown Live', 'NXT', 'Friday Night SmackDown',
            'Impact Wrestling', 'Dynamite', 'Rampage',
        ];

        $specialEvents = [
            'Night of Champions', 'King of the Ring', 'Great American Bash',
            'In Your House', 'No Way Out', 'Unforgiven', 'Vengeance',
            'Judgment Day', 'Bad Blood', 'No Mercy', 'Armageddon',
        ];

        $adjectives = [
            'Ultimate', 'Supreme', 'Mega', 'Super', 'Grand', 'Epic',
            'Legendary', 'Championship', 'Elite', 'Prime', 'Maximum',
        ];

        $eventTypes = [
            'Showdown', 'Clash', 'Battle', 'War', 'Rumble', 'Mayhem',
            'Revolution', 'Evolution', 'Invasion', 'Uprising', 'Domination',
        ];

        $patterns = [
            fn () => fake()->randomElement($famousEvents),
            fn () => fake()->randomElement($weeklyShows),
            fn () => fake()->randomElement($specialEvents),
            fn () => fake()->randomElement($adjectives).' '.fake()->randomElement($eventTypes),
        ];

        return fake()->randomElement($patterns)();
    }

    /**
     * Generate promotional preview content for wrestling events.
     *
     * Creates realistic promotional text that would be used to advertise
     * wrestling events, including match previews, storyline elements,
     * and promotional language typical of wrestling marketing.
     *
     * @return string Generated promotional preview text
     *
     * @example
     * Returns text like: "Don't miss the most anticipated wrestling event of the year..."
     */
    protected function generateEventPreview(): string
    {
        $openings = [
            "Don't miss the most anticipated wrestling event of the year",
            'Get ready for an unforgettable night of sports entertainment',
            'Witness history in the making at this epic wrestling spectacular',
            'The biggest superstars collide in this must-see wrestling event',
            'Experience the ultimate showdown between wrestling legends',
            'Prepare for an action-packed evening of championship wrestling',
        ];

        $middles = [
            'featuring championship matches, fierce rivalries, and unexpected surprises',
            'with title matches, grudge matches, and career-defining moments',
            'showcasing the best wrestlers from around the world',
            'bringing you non-stop action and unforgettable moments',
            'delivering high-impact matches and dramatic storylines',
            'presenting the ultimate test of strength, skill, and determination',
        ];

        $endings = [
            "Don't miss a single moment of the action!",
            "This is one event you won't want to miss!",
            'Be there live or watch on pay-per-view!',
            'Tickets are selling fast - get yours today!',
            'The excitement starts at 8 PM Eastern!',
            'Order now and be part of wrestling history!',
        ];

        return fake()->randomElement($openings).', '.
               fake()->randomElement($middles).'. '.
               fake()->randomElement($endings);
    }
}
