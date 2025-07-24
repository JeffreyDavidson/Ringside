<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Venues\Forms\CreateEditForm;
use App\Models\Events\Venue;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Venue>
 */
class FormModal extends BaseFormModal
{
    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    protected function getModelClass(): string
    {
        return Venue::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.venues.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        /**
         * @var string $state
         *
         * @phpstan-ignore-next-line
         */
        $state = fake('en_US')->state();

        return [
            'name' => fn () => Str::of(fake()->sentence(2))->title()->append(' Arena')->value(),
            'street_address' => fn () => fake()->streetAddress(),
            'city' => fn () => fake()->city(),
            'state' => fn () => $state,
            'zipcode' => fn () => (int) Str::of(fake()->postcode())->limit(5)->value(),
        ];
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit Venue';
        }

        return 'Create Venue';
    }

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.venues.modals.form-modal');
    }

    public function submitForm(): bool
    {
        // Store whether we're creating or updating before the form submission
        $isCreating = $this->form->isCreating();

        $result = parent::submitForm();

        if ($result) {
            // Dispatch the appropriate event based on whether we created or updated
            if ($isCreating) {
                $this->dispatch('venueCreated');
            } else {
                $this->dispatch('venueUpdated');
            }

            // Reset the form after successful submission
            $this->form->reset();
        }

        return $result;
    }

    public function closeModal(): void
    {
        parent::closeModal();
        // Reset the form when modal is closed
        $this->form->reset();
    }
}
