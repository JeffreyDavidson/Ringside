<?php

declare(strict_types=1);

namespace App\Livewire\Events\Forms;

use App\Livewire\Base\LivewireBaseForm;
use App\Livewire\Concerns\Data\PresentsVenuesList;
use App\Models\Events\Event;
use App\Rules\Events\DateCanBeChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Livewire form component for managing event creation and editing.
 *
 * This form handles the complete lifecycle of wrestling event management including
 * event scheduling, venue assignment, and promotional content management.
 * Provides specialized validation for event-specific requirements like date
 * constraints and venue availability.
 *
 * Key Responsibilities:
 * - Event information management (name, date, venue, preview)
 * - Date validation with custom business rules for event scheduling
 * - Venue relationship management and availability checking
 * - Promotional content handling for event marketing
 * - Event uniqueness validation across the system
 * - Cached venue list presentation for efficient form rendering
 *
 * @extends LivewireBaseForm<EventForm, Event>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality
 * @see PresentsVenuesList For venue selection functionality
 * @see Event For the underlying event model
 * @see DateCanBeChanged For custom date validation rules
 *
 * @property string $name Event name for promotional purposes
 * @property Carbon|string|null $date Scheduled event date
 * @property int $venue Venue ID for event location
 * @property string $preview Promotional preview text for marketing
 */
class EventForm extends LivewireBaseForm
{
    use PresentsVenuesList;

    /**
     * The model instance being edited, or null for new event creation.
     *
     * @var Event|null Current event model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Event name for promotional and administrative purposes.
     *
     * Used for event marketing, match cards, ticket sales, and internal
     * organization. Must be unique across all events in the system to
     * avoid confusion in scheduling and promotion.
     *
     * @var string Event's promotional name
     */
    public string $name = '';

    /**
     * Scheduled date and time for the wrestling event.
     *
     * Critical for venue booking, promotional scheduling, and match planning.
     * Validated against business rules to ensure proper event scheduling
     * and avoid conflicts with existing events or venue availability.
     *
     * @var Carbon|string|null Event date and time
     */
    public Carbon|string|null $date = '';

    /**
     * Venue identifier for event location assignment.
     *
     * Links the event to a specific venue for logistical planning,
     * capacity management, and promotional materials. Required when
     * an event date is set to ensure proper venue booking.
     *
     * The PresentsVenuesList trait provides the getVenues() method
     * for populating venue selection dropdowns in the UI.
     *
     * @var int Venue database ID
     *
     * @see getVenues() For venue selection list generation
     */
    public int $venue = 0;

    /**
     * Promotional preview content for marketing purposes.
     *
     * Used in promotional materials, websites, social media, and
     * ticketing platforms to generate interest and provide event
     * information to potential attendees.
     *
     * @var string|null Marketing preview text
     */
    public ?string $preview = '';

    /**
     * Load additional data when editing existing event records.
     *
     * Handles any specialized data loading for event edit operations.
     * Currently no additional data loading is required for events
     * beyond the standard form fill mechanism and venue list caching.
     *
     * The venue list is automatically cached via the PresentsVenuesList
     * trait's computed property system for efficient rendering.
     *
     *
     * @see PresentsVenuesList::getVenues() For venue list caching
     */
    public function loadExtraData(): void
    {
        // Map venue_id from model to venue form field
        if ($this->formModel && isset($this->formModel->venue_id)) {
            $this->venue = $this->formModel->venue_id;
        }

        // Venue list is cached automatically via PresentsVenuesList trait
    }

    /**
     * Prepare event-specific data for model storage.
     *
     * Transforms form fields into model-compatible data structure,
     * ensuring proper field mapping and data type conversion for
     * database persistence. Handles venue relationship mapping.
     *
     * Data Transformations:
     * - Maps venue field to venue_id for proper foreign key relationship
     * - Passes through date with proper Carbon/string handling
     * - Maintains other fields with appropriate data types
     *
     * @return array<string, mixed> Model data ready for persistence
     *
     * @see Event For model field requirements and relationships
     */
    protected function getModelData(): array
    {
        return [
            'name' => $this->name,
            'date' => $this->date,
            'venue_id' => $this->venue,
            'preview' => $this->preview,
        ];
    }

    /**
     * Get the model class for event form operations.
     *
     * Specifies the Event model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<Event> The Event model class
     */
    protected function getModelClass(): string
    {
        return Event::class;
    }

    /**
     * Define validation rules for event form fields.
     *
     * Provides comprehensive validation for all event data including
     * uniqueness constraints, date validation with custom business rules,
     * venue relationship validation, and content requirements.
     *
     * The venue validation uses the exists rule to ensure the selected
     * venue is available in the venues table, working in conjunction
     * with the PresentsVenuesList trait's venue selection functionality.
     *
     * Validation Requirements:
     * - Name: Required, unique across events, max 255 characters
     * - Date: Optional, valid date format, custom business rule validation
     * - Venue: Required when date is provided, must exist in venues table
     * - Preview: Required promotional content, string format
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     *
     * @see DateCanBeChanged For custom date validation logic
     * @see Rule::unique() For database uniqueness constraints
     * @see Rule::exists() For foreign key validation
     * @see PresentsVenuesList::getVenues() For venue selection options
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('events', 'name')->ignore($this->formModel)],
            'date' => ['nullable', 'date', new DateCanBeChanged($this->formModel)],
            'venue' => ['required_with:date', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string'],
        ];
    }

    /**
     * Get event-specific validation attributes.
     *
     * All standard attributes are provided by HasStandardValidationAttributes trait.
     * This method handles event-specific field naming.
     *
     * @return array<string, string> Custom validation attributes for this form
     */
    protected function getCustomValidationAttributes(): array
    {
        return [
            'date' => 'event date',
            'venue' => 'venue',
            'preview' => 'event preview',
        ];
    }
}
