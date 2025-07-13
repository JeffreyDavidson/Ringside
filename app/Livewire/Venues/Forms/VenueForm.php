<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Forms;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Shared\Venue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Livewire form component for managing venue creation and editing.
 *
 * This form handles venue location data management for wrestling events and shows.
 * Provides validation for complete venue information including address verification
 * and state existence validation. Venues represent physical locations where
 * wrestling events take place, requiring accurate location data for event planning,
 * fan travel, and operational logistics.
 *
 * Key Responsibilities:
 * - Venue identification and naming with uniqueness enforcement
 * - Complete address management with comprehensive validation
 * - State verification against valid state records in database
 * - ZIP code format validation for postal accuracy
 * - Location data integrity for event management systems
 *
 * @extends LivewireBaseForm<VenueForm, Venue>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see LivewireBaseForm For base form functionality and patterns
 *
 * @property string $name Venue's official name for events and promotion
 * @property string $street_address Complete street address for location
 * @property string $city City where venue is located
 * @property string $state State name (validated against State model)
 * @property int|string $zipcode 5-digit ZIP code for postal addressing
 */
class VenueForm extends LivewireBaseForm
{
    /**
     * The model instance being edited, or null for new venue creation.
     *
     * @var Venue|null Current venue model or null for creation
     */
    protected ?Model $formModel = null;

    /**
     * Venue's official name for events and promotional materials.
     *
     * Used in event announcements, ticket sales, promotional content,
     * and venue booking systems. Must be unique across all venues
     * to prevent confusion in event scheduling and fan communications.
     *
     * @var string Venue's primary name identifier
     */
    public string $name = '';

    /**
     * Complete street address including number and street name.
     *
     * Full physical address for venue location, essential for GPS navigation,
     * shipping logistics, emergency services, and official documentation.
     * Combined with city, state, and ZIP code for complete addressing.
     *
     * @var string Street address for venue location
     */
    public string $street_address = '';

    /**
     * City where the venue is located.
     *
     * Municipal location for the venue, used in event announcements,
     * marketing materials, and fan travel planning. Critical for regional
     * event scheduling and local promotional partnerships.
     *
     * @var string City name for venue location
     */
    public string $city = '';

    /**
     * State where the venue is located.
     *
     * Validated against existing State model records to ensure data accuracy
     * and prevent entry errors. Used for regional event planning, tax compliance,
     * regulatory requirements, and state-specific operational procedures.
     *
     * @var string State name (must exist in states table)
     */
    public string $state = '';

    /**
     * 5-digit ZIP code for postal addressing.
     *
     * Standard US postal code format essential for mail delivery, location
     * identification, and regional analysis. Validated as exactly 5 digits
     * to ensure proper format for shipping and correspondence systems.
     *
     * @var int|string ZIP code in 5-digit format
     */
    public int|string $zipcode = '';

    /**
     * Load additional data when editing existing venue records.
     *
     * Handles data loading for venue-specific fields when editing
     * existing venues. Called automatically during form initialization
     * for edit operations. No special data transformation needed for venues.
     */
    public function loadExtraData(): void
    {
        // No additional data loading required for venues
        // All venue data is loaded via standard form fill mechanism
    }

    /**
     * Prepare venue data for model storage.
     *
     * Transforms form fields into model-compatible data structure ready
     * for database persistence. All venue fields are passed through directly
     * as they represent simple scalar values without complex transformations.
     *
     * @return array<string, mixed> Model data ready for persistence
     */
    protected function getModelData(): array
    {
        return [
            'name' => $this->name,
            'street_address' => $this->street_address,
            'city' => $this->city,
            'state' => $this->state,
            'zipcode' => $this->zipcode,
        ];
    }

    /**
     * Get the model class for venue form operations.
     *
     * Specifies the Venue model class for type-safe model operations
     * including creation, updates, and relationship management.
     *
     * @return class-string<Venue> The Venue model class
     */
    protected function getModelClass(): string
    {
        return Venue::class;
    }

    /**
     * Define validation rules for venue form fields.
     *
     * Provides comprehensive validation for all venue location data including
     * uniqueness constraints, address completeness, state existence verification,
     * and ZIP code format validation to ensure operational reliability.
     *
     * @return array<string, array<int, mixed>> Laravel validation rules array
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('venues', 'name')->ignore($this->formModel)],
            'street_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', Rule::exists('states', 'name')],
            'zipcode' => ['required', 'digits:5'],
        ];
    }

    /**
     * Get venue-specific validation attributes.
     *
     * Extends standard attributes with venue-specific field names for better
     * user experience in validation messages.
     *
     * @return array<string, string> Custom validation attributes for this form
     */
    protected function validationAttributes(): array
    {
        return [
            'street_address' => 'street address',
            'zipcode' => 'zip code',
        ];
    }
}
