<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Referees\Forms\RefereeForm;
use App\Models\Referees\Referee;
use Livewire\Form;

/**
 * Livewire modal component for wrestling referee management.
 *
 * Handles the creation and editing of wrestling referee personnel including
 * personal information, career start dates, and official credentials.
 * Provides specialized data generation for referee testing and development.
 *
 * Key Features:
 * - Modal-based referee form interface
 * - Realistic referee name generation
 * - Career start date generation
 * - Form validation with referee-specific rules
 * - Integration with referee management workflows
 *
 * @extends BaseFormModal<RefereeForm, Referee>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseFormModal For modal functionality and patterns
 * @see Referee For the underlying referee model structure
 */
class RefereeFormModal extends BaseFormModal
{
    /**
     * The referee form instance for data management.
     *
     * Handles all referee-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public Form $form;

    /**
     * Get the form class that handles referee data validation and processing.
     *
     * @return class-string<RefereeForm> The fully qualified class name of RefereeForm
     */
    protected function getFormClass(): string
    {
        return RefereeForm::class;
    }

    /**
     * Get the model class that represents referee entities.
     *
     * @return class-string<Referee> The fully qualified class name of Referee model
     */
    protected function getModelClass(): string
    {
        return Referee::class;
    }

    /**
     * Get the Blade view path for rendering the referee form modal.
     *
     * @return string The view path relative to resources/views
     */
    protected function getModalPath(): string
    {
        return 'referees.modals.form-modal';
    }

    /**
     * Generate dummy data fields for referee testing and development.
     *
     * Returns field generators for referee data including realistic names
     * and career start dates appropriate for wrestling officials.
     *
     * @return array<string, callable(): mixed> Array mapping field names to generators
     */
    protected function getDummyDataFields(): array
    {
        return [
            'first_name' => fn () => $this->generateRefereeFirstName(),
            'last_name' => fn () => $this->generateRefereeLastName(),
            'employment_date' => fn () => fake()->optional(0.8, fn () => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d H:i:s')),
        ];
    }

    /**
     * Generate random referee data for testing and development.
     *
     * Populates the form with realistic referee data including names and
     * career start dates. Generates data appropriate for wrestling referee
     * personnel records and testing workflows.
     */
    public function generateRandomData(): void
    {
        $this->form->fill([
            'first_name' => $this->generateRefereeFirstName(),
            'last_name' => $this->generateRefereeLastName(),
            'employment_date' => fake()->optional(0.8, fn () => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d H:i:s')),
        ]);
    }

    /**
     * Generate a realistic referee first name.
     *
     * Creates first names that are appropriate for wrestling referee
     * personnel, following common naming patterns for sports officials.
     *
     * @return string A generated first name
     *
     * @example
     * Returns names like: "Earl", "Mike", "Charles", "John"
     */
    protected function generateRefereeFirstName(): string
    {
        // Mix of famous wrestling referee names and common professional names
        $famousRefereeNames = [
            'Earl', 'Mike', 'Charles', 'Tim', 'John', 'Dave', 'Red', 'Danny',
            'Teddy', 'Nick', 'Scott', 'Chad', 'Darrick', 'Rod', 'Robinson',
        ];

        $commonNames = [
            'James', 'Robert', 'Michael', 'William', 'David', 'Richard',
            'Joseph', 'Thomas', 'Charles', 'Christopher', 'Daniel', 'Matthew',
            'Anthony', 'Mark', 'Donald', 'Steven', 'Paul', 'Andrew', 'Joshua',
        ];

        $patterns = [
            // 60% chance of famous referee names
            fn () => fake()->randomElement($famousRefereeNames),
            // 40% chance of common professional names
            fn () => fake()->randomElement($commonNames),
        ];

        return fake()->randomFloat(null, 0, 1) < 0.6
            ? $patterns[0]()
            : $patterns[1]();
    }

    /**
     * Generate a realistic referee last name.
     *
     * Creates last names that are appropriate for wrestling referee
     * personnel, including famous referee surnames and common surnames.
     *
     * @return string A generated last name
     *
     * @example
     * Returns names like: "Hebner", "Robinson", "Armstrong", "Chioda"
     */
    protected function generateRefereeLastName(): string
    {
        // Famous wrestling referee surnames
        $famousRefereeSurnames = [
            'Hebner', 'Robinson', 'Armstrong', 'Chioda', 'Edwards', 'Korderas',
            'White', 'Long', 'Cone', 'Guerrera', 'Zamora', 'Ruiz', 'Doan',
        ];

        $commonSurnames = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia',
            'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez',
            'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor',
        ];

        $patterns = [
            // 50% chance of famous referee surnames
            fn () => fake()->randomElement($famousRefereeSurnames),
            // 50% chance of common surnames
            fn () => fake()->randomElement($commonSurnames),
        ];

        return fake()->randomFloat(null, 0, 1) < 0.5
            ? $patterns[0]()
            : $patterns[1]();
    }
}
