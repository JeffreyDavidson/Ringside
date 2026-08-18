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
    protected ?string $createdEventName = 'venueCreated';

    protected ?string $updatedEventName = 'venueUpdated';

    protected bool $resetFormAfterSubmission = true;

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

    protected function populateDummyData(): void
    {
        /**
         * @var string $state
         *
         * @phpstan-ignore-next-line
         */
        $state = fake('en_US')->state();

        $this->form->name = Str::of(fake()->sentence(2))->title()->append(' Arena')->value();
        $this->form->street_address = fake()->streetAddress();
        $this->form->city = fake()->city();
        $this->form->state = $state;
        $this->form->zipcode = fake('en_US')->numerify('#####');
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

    public function closeModal(): void
    {
        parent::closeModal();
        // Reset the form when modal is closed
        $this->form->reset();
    }
}
