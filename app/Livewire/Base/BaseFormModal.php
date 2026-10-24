<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use App\Livewire\Concerns\GeneratesDummyData;
use App\Livewire\Concerns\ReportsBusinessErrors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LogicException;

/**
 * @template TForm of BaseForm
 * @template TModel of Model
 *
 * @property TForm $form
 *
 * @extends BaseModal<TForm, TModel>
 */
abstract class BaseFormModal extends BaseModal
{
    use GeneratesDummyData;
    use ReportsBusinessErrors;

    protected ?string $createdEventName = null;

    protected ?string $updatedEventName = null;

    protected bool $resetFormAfterSubmission = false;

    public function save(): void
    {
        $this->submitForm();
    }

    public function submitForm(): bool
    {
        $wasCreating = $this->form->isCreating();

        $this->authorizeFormAccess();

        if (! $this->storeForm()) {
            return false;
        }

        $this->dispatch('refreshDatatable');
        $this->dispatch('closeModal');

        $eventName = $wasCreating
            ? $this->createdEventName
            : $this->updatedEventName;

        if ($eventName !== null) {
            $this->dispatch($eventName);
        }

        if ($this->resetFormAfterSubmission) {
            $this->form->reset();
        }

        return true;
    }

    /**
     * Validate and persist the form through createForm()/updateForm().
     *
     * A business rule failure from the Action becomes an error on the field named by
     * businessErrorField() and returns false, which keeps the modal open. Validation failures
     * throw instead.
     */
    protected function storeForm(): bool
    {
        return $this->reportBusinessErrors(function (): void {
            $this->form->validate();

            if ($this->form->isEditing()) {
                $this->updateForm();

                return;
            }

            $this->createForm();
        });
    }

    /** Must be overridden unless the modal overrides storeForm() without calling parent::storeForm(). */
    protected function createForm(): void
    {
        throw new LogicException('A form modal must define createForm().');
    }

    /** Must be overridden unless the modal overrides storeForm() without calling parent::storeForm(). */
    protected function updateForm(): void
    {
        throw new LogicException('A form modal must define updateForm().');
    }

    /** Needs the ability that opening the form needs: `create`, or `update` on the edited record. */
    protected function authorizeFormAccess(): void
    {
        $modelClass = $this->getModelClass();

        if ($this->form->isCreating()) {
            Gate::authorize('create', $modelClass);

            return;
        }

        Gate::authorize('update', $modelClass::query()->findOrFail($this->form->modelId));
    }

    /** @return TForm */
    protected function getModelForm(): BaseForm
    {
        return $this->form;
    }
}
