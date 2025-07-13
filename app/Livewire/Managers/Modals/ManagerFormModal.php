<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Managers\Forms\ManagerForm;
use App\Models\Managers\Manager;
use Livewire\Form;

/**
 * Livewire modal component for wrestling manager management.
 *
 * Handles the creation and editing of wrestling manager personnel including
 * personal information, career start dates, and managerial credentials.
 * Managers typically handle wrestler representation and storyline development.
 *
 * Key Features:
 * - Modal-based manager form interface
 * - Wrestling manager name generation
 * - Career start date generation
 * - Form validation with manager-specific rules
 * - Integration with manager management workflows
 *
 * @extends BaseFormModal<ManagerForm, Manager>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseFormModal For modal functionality and patterns
 * @see Manager For the underlying manager model structure
 */
class ManagerFormModal extends BaseFormModal
{
    /**
     * The manager form instance for data management.
     *
     * Handles all manager-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public ManagerForm $form;

    /**
     * Get the form class that handles manager data validation and processing.
     *
     * @return class-string<ManagerForm> The fully qualified class name of ManagerForm
     */
    protected function getFormClass(): string
    {
        return ManagerForm::class;
    }

    /**
     * Get the model class that represents manager entities.
     *
     * @return class-string<Manager> The fully qualified class name of Manager model
     */
    protected function getModelClass(): string
    {
        return Manager::class;
    }

    /**
     * Get the Blade view path for rendering the manager form modal.
     *
     * @return string The view path relative to resources/views
     */
    protected function getModalPath(): string
    {
        return 'managers.modals.form-modal';
    }

    /**
     * Generate dummy data fields for manager testing and development.
     *
     * Returns field generators for manager data including realistic names
     * and career start dates appropriate for wrestling managerial personnel.
     *
     * @return array<string, callable(): mixed> Array mapping field names to generators
     */
    protected function getDummyDataFields(): array
    {
        return [
            'first_name' => fn () => $this->generateManagerFirstName(),
            'last_name' => fn () => $this->generateManagerLastName(),
            'employment_date' => fake()->optional(0.8, fn () => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d H:i:s')),
        ];
    }

    /**
     * Generate random manager data for testing and development.
     *
     * Populates the form with realistic manager data including names and
     * career start dates. Generates data appropriate for wrestling manager
     * personnel records and testing workflows.
     */
    public function generateRandomData(): void
    {
        $this->form->fill([
            'first_name' => $this->generateManagerFirstName(),
            'last_name' => $this->generateManagerLastName(),
            'employment_date' => fake()->optional(0.8, fn () => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d H:i:s')),
        ]);
    }

    /**
     * Generate a realistic wrestling manager first name.
     *
     * Creates first names that are appropriate for wrestling manager
     * personnel, including famous manager names and professional names
     * suitable for managerial roles in wrestling.
     *
     * @return string A generated first name
     *
     * @example
     * Returns names like: "Paul", "Bobby", "Jimmy", "Vickie"
     */
    protected function generateManagerFirstName(): string
    {
        // Famous wrestling manager names
        $famousManagerNames = [
            'Paul', 'Bobby', 'Jimmy', 'Vickie', 'Stephanie', 'Triple',
            'Vince', 'Eric', 'Teddy', 'William', 'Dutch', 'Gorilla',
            'Jesse', 'Jerry', 'Jim', 'Lana', 'Zelina', 'Lio',
        ];

        $professionalNames = [
            'Alexander', 'Benjamin', 'Catherine', 'Dominic', 'Elizabeth',
            'Frederick', 'Gabrielle', 'Harrison', 'Isabella', 'Jonathan',
            'Katherine', 'Lawrence', 'Margaret', 'Nathaniel', 'Olivia',
            'Patricia', 'Quinton', 'Rebecca', 'Sebastian', 'Victoria',
        ];

        $patterns = [
            // 70% chance of famous manager names
            fn () => fake()->randomElement($famousManagerNames),
            // 30% chance of professional names
            fn () => fake()->randomElement($professionalNames),
        ];

        return fake()->randomFloat(null, 0, 1) < 0.7
            ? $patterns[0]()
            : $patterns[1]();
    }

    /**
     * Generate a realistic wrestling manager last name.
     *
     * Creates last names that are appropriate for wrestling manager
     * personnel, including famous manager surnames and professional
     * surnames suitable for executive and managerial positions.
     *
     * @return string A generated last name
     *
     * @example
     * Returns names like: "Heyman", "Heenan", "Hart", "McMahon"
     */
    protected function generateManagerLastName(): string
    {
        // Famous wrestling manager surnames
        $famousManagerSurnames = [
            'Heyman', 'Heenan', 'Hart', 'McMahon', 'Guerrero', 'Cornette',
            'Fuji', 'Blassie', 'Albano', 'Bearer', 'Long', 'Regal',
            'Stephanie', 'Dolph', 'Lashley', 'Vega', 'Rush', 'Banks',
        ];

        $professionalSurnames = [
            'Anderson', 'Barrett', 'Campbell', 'Davidson', 'Edwards',
            'Fitzgerald', 'Goldman', 'Harrison', 'Jackson', 'Kingston',
            'Lancaster', 'Morrison', 'Newcastle', 'Patterson', 'Richardson',
            'Stevenson', 'Thompson', 'Wellington', 'Worthington', 'York',
        ];

        $patterns = [
            // 60% chance of famous manager surnames
            fn () => fake()->randomElement($famousManagerSurnames),
            // 40% chance of professional surnames
            fn () => fake()->randomElement($professionalSurnames),
        ];

        return fake()->randomFloat(null, 0, 1) < 0.6
            ? $patterns[0]()
            : $patterns[1]();
    }
}
