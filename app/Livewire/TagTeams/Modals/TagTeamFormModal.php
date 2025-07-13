<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\TagTeams\Forms\TagTeamForm;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Livewire\Form;

/**
 * Livewire modal component for wrestling tag team management.
 *
 * Manages the creation and editing of tag team partnerships, including
 * team naming, wrestler relationships, signature moves, and career timelines.
 * Provides specialized data generation for tag team testing and development.
 *
 * Key Features:
 * - Modal-based tag team form interface
 * - Wrestling tag team name generation
 * - Automatic wrestler relationship creation
 * - Signature move generation for teams
 * - Integration with tag team management workflows
 *
 * @extends BaseFormModal<TagTeamForm, TagTeam>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see BaseFormModal For modal functionality and patterns
 * @see TagTeam For the underlying tag team model structure
 */
class TagTeamFormModal extends BaseFormModal
{
    use PresentsWrestlersList;

    /**
     * The tag team form instance for data management.
     *
     * Handles all tag team-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public TagTeamForm $form;

    /**
     * Get the form class that handles tag team data validation and processing.
     *
     * @return class-string<TagTeamForm> The fully qualified class name of TagTeamForm
     */
    protected function getFormClass(): string
    {
        return TagTeamForm::class;
    }

    /**
     * Get the model class that represents tag team entities.
     *
     * @return class-string<TagTeam> The fully qualified class name of TagTeam model
     */
    protected function getModelClass(): string
    {
        return TagTeam::class;
    }

    /**
     * Get the Blade view path for rendering the tag team form modal.
     *
     * @return string The view path relative to resources/views
     */
    protected function getModalPath(): string
    {
        return 'tag-teams.modals.form-modal';
    }

    /**
     * Generate dummy data fields for tag team testing and development.
     *
     * Returns field generators for tag team data including wrestling-appropriate
     * names, wrestler relationships, signature moves, and formation dates.
     *
     * @return array<string, callable(): mixed> Array mapping field names to generators
     */
    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => fake()->words(3, true),
            'employment_date' => fake()->optional(0.8, fn () => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d H:i:s')),
            'signature_move' => fake()->optional(0.7, fn () => fake()->words(3, true)),
            /** @phpstan-ignore-next-line */
            'wrestlerA' => fn () => Wrestler::inRandomOrder()->first()?->id ?? Wrestler::factory()->create()->id,
            /** @phpstan-ignore-next-line */
            'wrestlerB' => fn () => Wrestler::inRandomOrder()->first()?->id ?? Wrestler::factory()->create()->id,
            /** @phpstan-ignore-next-line */
            'managers' => fn () => Manager::inRandomOrder()->first()?->id ?? Manager::factory()->create()->id,
        ];
    }
}
