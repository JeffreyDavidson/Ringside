<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Venues\Forms\VenueForm;
use App\Models\Shared\Venue;
use Livewire\Form;

/**
 * Livewire modal component for venue form management.
 *
 * Manages the creation and editing of wrestling venue entities. Handles
 * venue-specific data including location information, address details,
 * and geographical data for wrestling events and shows.
 *
 * Key Features:
 * - Modal-based venue form interface
 * - Automatic data generation for testing and development
 * - Form validation with user feedback
 * - Creation and editing mode support
 * - Integration with venue-specific business logic
 *
 * @extends BaseFormModal<VenueForm, Venue>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseFormModal For modal functionality and patterns
 * @see Venue For the underlying venue model structure
 */
class VenueFormModal extends BaseFormModal
{
    /**
     * The venue form instance for data management.
     *
     * Handles all venue-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public VenueForm $form;

    /**
     * Get the form class that handles venue data validation and processing.
     *
     * @return class-string<VenueForm> The fully qualified class name of VenueForm
     */
    protected function getFormClass(): string
    {
        return VenueForm::class;
    }

    /**
     * Get the model class that represents venue entities.
     *
     * @return class-string<Venue> The fully qualified class name of Venue model
     */
    protected function getModelClass(): string
    {
        return Venue::class;
    }

    /**
     * Get the Blade view path for rendering the venue form modal.
     *
     * @return string The view path relative to resources/views
     */
    protected function getModalPath(): string
    {
        return 'venues.modals.form-modal';
    }

    /**
     * Generate dummy data fields for venue form testing and development.
     *
     * Creates realistic test data for venue locations including:
     * - Venue names (arenas, stadiums, theaters)
     * - Complete US address information
     * - Street address, city, state, and ZIP code
     *
     * Uses optimized approach to generate address data once and reuse
     * across multiple fields for consistency and efficiency.
     *
     * @return array<string, callable(): mixed> Array mapping field names to generators
     */
    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => $this->generateVenueName(),
            'street_address' => fn () => $this->generateUSAddress()['street_address'],
            'city' => fn () => $this->generateUSAddress()['city'],
            'state' => fn () => $this->generateUSAddress()['state'],
            'zipcode' => fn () => $this->generateUSAddress()['zipcode'],
        ];
    }

    /**
     * Generate random venue data for testing and development.
     *
     * Populates the form with realistic venue data including names and
     * complete address information. Uses consistent address generation
     * to ensure all address fields are from the same location.
     */
    public function generateRandomData(): void
    {
        // Generate address data once for consistency
        $addressData = $this->generateUSAddress();

        $this->form->fill([
            'name' => $this->generateVenueName(),
            'street_address' => $addressData['street_address'],
            'city' => $addressData['city'],
            'state' => $addressData['state'],
            'zipcode' => $addressData['zipcode'],
        ]);
    }
}
