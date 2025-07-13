<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Titles\Forms\TitleForm;
use App\Models\Titles\Title;
use Livewire\Form;

/**
 * Livewire modal component for championship title form management.
 *
 * Manages the creation and editing of championship titles within the wrestling
 * management system. Provides specialized data generation for championship
 * titles following wrestling industry naming conventions and activation patterns.
 *
 * Key Features:
 * - Modal-based title form interface
 * - Wrestling-specific title name generation
 * - Automatic activation date generation
 * - Form validation with championship naming rules
 * - Integration with title management workflows
 *
 * @extends BaseFormModal<TitleForm, Title>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseFormModal For modal functionality and patterns
 * @see Title For the underlying title model structure
 */
class TitleFormModal extends BaseFormModal
{
    /**
     * The title form instance for data management.
     *
     * Handles all title-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public TitleForm $form;

    /**
     * Get the form class that handles title data validation and processing.
     *
     * @return class-string<TitleForm> The fully qualified class name of TitleForm
     */
    protected function getFormClass(): string
    {
        return TitleForm::class;
    }

    /**
     * Get the model class that represents title entities.
     *
     * @return class-string<Title> The fully qualified class name of Title model
     */
    protected function getModelClass(): string
    {
        return Title::class;
    }

    /**
     * Get the Blade view path for rendering the title form modal.
     *
     * @return string The view path relative to resources/views
     */
    protected function getModalPath(): string
    {
        return 'titles.modals.form-modal';
    }

    /**
     * Generate dummy data fields for title form testing and development.
     *
     * Returns field generators for championship title data including wrestling-
     * appropriate names and activation dates following industry conventions.
     *
     * @return array<string, callable(): mixed> Array mapping field names to generators
     */
    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => $this->generateChampionshipTitle(),
            'type' => fn () => fake()->randomElement(['singles', 'tag-team']),
            'start_date' => fake()->optional(0.8, fn () => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d H:i:s')),
        ];
    }

    /**
     * Generate random title data for testing and development.
     *
     * Populates the form with realistic championship title data including
     * wrestling-appropriate names and activation dates. Ensures all generated
     * data follows championship naming conventions and validation rules.
     */
    public function generateRandomData(): void
    {
        $this->form->fill([
            'name' => $this->generateChampionshipTitle(),
            'type' => fake()->randomElement(['singles', 'tag-team']),
            'start_date' => fake()->optional(0.8, fn () => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d H:i:s')),
        ]);
    }
}
