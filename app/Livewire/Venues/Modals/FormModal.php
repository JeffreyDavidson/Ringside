<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Modals;

use App\Actions\Venues\CreateAction;
use App\Actions\Venues\UpdateAction;
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
    public CreateEditForm $form;

    private CreateAction $createAction;

    private UpdateAction $updateAction;

    public function boot(CreateAction $createAction, UpdateAction $updateAction): void
    {
        $this->createAction = $createAction;
        $this->updateAction = $updateAction;
    }

    protected function getModelClass(): string
    {
        return Venue::class;
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
            'zipcode' => fn () => fake('en_US')->numerify('#####'),
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
        return view('livewire.venues.modals.form-modal');
    }

    protected function storeForm(): bool
    {
        $this->form->validate();

        if ($this->form->isEditing()) {
            $this->updateAction->handle($this->form->venue(), $this->form->toData());

            return true;
        }

        $this->createAction->handle($this->form->toData());

        return true;
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
