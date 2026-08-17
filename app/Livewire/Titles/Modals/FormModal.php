<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Modals;

use App\Actions\Titles\CreateAction;
use App\Actions\Titles\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Titles\Forms\CreateEditForm;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Title>
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
        return Title::class;
    }

    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => Str::of(fake()->words(2, true))->title()->append(' Title')->value(),
            'type' => fn () => fake()->randomElement(['singles', 'tag-team']),
            'start_date' => fn () => $this->generateOptionalStartDate('Y-m-d', 0.6, '-1 year', 'now'),
        ];
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit Title';
        }

        return 'Create Title';
    }

    public function openModal(mixed $modelId = null): void
    {
        // Authorization check - only administrators can access title management
        if ($modelId) {
            $title = Title::findOrFail($modelId);
            Gate::authorize('update', $title);
        } else {
            Gate::authorize('create', Title::class);
        }

        parent::openModal($modelId);
    }

    protected function storeForm(): bool
    {
        $this->form->validate();

        if ($this->form->isEditing()) {
            $this->updateAction->handle($this->form->title(), $this->form->toData());

            return true;
        }

        $this->createAction->handle($this->form->toData());

        return true;
    }

    public function render(): View
    {
        return view('livewire.titles.modals.form-modal');
    }
}
