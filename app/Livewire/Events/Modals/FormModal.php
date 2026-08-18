<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Actions\Events\CreateAction;
use App\Actions\Events\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsVenuesList;
use App\Livewire\Events\Forms\CreateEditForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Event>
 */
class FormModal extends BaseFormModal
{
    use PresentsVenuesList;

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
        return Event::class;
    }

    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => Str::of(fake()->sentence(2))->title()->value(),
            'date' => fn (): string => fake()->dateTimeBetween('now', '+3 month')->format('Y-m-d H:i:s'),
            'venue_id' => fn () => Venue::query()->inRandomOrder()->value('id'),
            'preview' => fn () => Str::of(fake()->text())->value(),
        ];
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit Event';
        }

        return 'Create Event';
    }

    protected function storeForm(): bool
    {
        $this->form->validate();

        if ($this->form->isEditing()) {
            $this->updateAction->handle($this->form->event(), $this->form->toData());

            return true;
        }

        $this->createAction->handle($this->form->toData());

        return true;
    }

    public function render(): View
    {
        return view('livewire.events.modals.form-modal');
    }
}
