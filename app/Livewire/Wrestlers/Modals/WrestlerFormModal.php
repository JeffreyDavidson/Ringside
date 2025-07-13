<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Wrestlers\Forms\WrestlerForm;
use App\Models\Wrestlers\Wrestler;

/**
 * Modal for creating and editing wrestlers.
 *
 * This modal provides a user-friendly interface for wrestler management,
 * including creation of new wrestler profiles and editing of existing ones.
 * It integrates dummy data generation for development and testing purposes.
 *
 * @extends BaseFormModal<WrestlerForm, Wrestler>
 *
 * @author Your Name
 *
 * @since 1.0.0
 */
class WrestlerFormModal extends BaseFormModal
{
    /**
     * The wrestler form instance for data management.
     *
     * Handles all wrestler-specific validation, data transformation,
     * and persistence operations within the modal interface.
     */
    public WrestlerForm $form;

    /**
     * Get the form class for wrestler management.
     *
     * @return class-string<WrestlerForm>
     */
    protected function getFormClass(): string
    {
        return WrestlerForm::class;
    }

    /**
     * Get the model class for wrestlers.
     *
     * @return class-string<Wrestler>
     */
    protected function getModelClass(): string
    {
        return Wrestler::class;
    }

    /**
     * Get the Blade view path for the wrestler modal.
     */
    protected function getModalPath(): string
    {
        return 'wrestlers.modals.form-modal';
    }

    /**
     * Get dummy data fields for wrestler forms.
     *
     * Provides realistic fake data for development and testing purposes,
     * using wrestling-specific generators and matching the exact fields
     * from WrestlerForm. All field names match the form properties.
     *
     * @return array<string, callable(): mixed>
     */
    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => $this->generateWrestlingName(),
            'hometown' => fn () => fake()->city().', '.fake()->randomElement([
                'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
                'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
                'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
                'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
                'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
            ]),
            'height_feet' => fn () => fake()->numberBetween(5, 7),
            'height_inches' => fn () => fake()->numberBetween(0, 11),
            'weight' => fn () => fake()->numberBetween(150, 350),
            'signature_move' => fn () => $this->generateSignatureMove(),
            'employment_date' => fn () => fake()->dateTimeBetween('-2 years', '-1 month')->format('Y-m-d H:i:s'),
        ];
    }
}
