<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsManagersList;
use App\Livewire\Concerns\Data\PresentsTagTeamsList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\Stables\Forms\StableForm;
use App\Models\Stables\Stable;

/**
 * Livewire modal component for wrestling stable management.
 *
 * Manages the creation and editing of wrestling stables (groups of wrestlers,
 * tag teams, and managers). Provides interfaces for building complex wrestling
 * faction relationships and hierarchies within the organization.
 *
 * @extends BaseFormModal<StableForm, Stable>
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see StableForm For stable validation and processing
 * @see Stable For the underlying stable model structure
 * @see PresentsManagersList For manager selection interface
 * @see PresentsTagTeamsList For tag team selection interface
 * @see PresentsWrestlersList For wrestler selection interface
 * @see BaseFormModal For inherited modal functionality
 */
class StableFormModal extends BaseFormModal
{
    use PresentsManagersList;
    use PresentsTagTeamsList;
    use PresentsWrestlersList;

    /**
     * The stable form instance for data management.
     *
     * Handles all stable-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public StableForm $form;

    /**
     * Get the form class that handles stable data validation and processing.
     *
     * @return class-string<StableForm> The fully qualified class name of StableForm
     */
    protected function getFormClass(): string
    {
        return StableForm::class;
    }

    /**
     * Get the model class that represents stable entities.
     *
     * @return class-string<Stable> The fully qualified class name of Stable model
     */
    protected function getModelClass(): string
    {
        return Stable::class;
    }

    /**
     * Get the Blade view path for rendering the stable form modal.
     *
     * @return string The view path relative to resources/views
     */
    protected function getModalPath(): string
    {
        return 'stables.modals.form-modal';
    }

    /**
     * Generate dummy data fields for stable testing and development.
     *
     * Creates realistic stable data including:
     * - Faction names and branding
     * - Formation dates for the group
     *
     * Uses local name generation for wrestling faction names
     * that work well for wrestling faction names and group identities.
     *
     * @return array<string, callable(): mixed> Associative array where keys are field names
     *                                          and values are closures that generate the dummy data
     *
     * @example
     * // Example generated data:
     * // [
     * //     'name' => fn() => 'The Corporation',
     * //     'start_date' => fn() => '2024-11-01 00:00:00'
     * // ]
     */
    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn (): string => $this->generateStableName(),
            'start_date' => fake()->optional(0.8, fn () => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d H:i:s')),
        ];
    }

    /**
     * Generate a wrestling stable name locally for this form.
     */
    private function generateStableName(): string
    {
        $prefixes = ['The', 'Team', 'Faction'];
        $types = ['Corporation', 'Alliance', 'Brotherhood', 'Syndicate', 'Elite', 'Order', 'Nation'];

        $prefix = fake()->randomElement($prefixes);
        $type = fake()->randomElement($types);

        return "{$prefix} {$type}";
    }
}
