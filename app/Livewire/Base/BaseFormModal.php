<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\GeneratesDummyData;
use Closure;
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

    protected ?string $createdEventName = null;

    protected ?string $updatedEventName = null;

    protected bool $resetFormAfterSubmission = false;

    /** The form field that shows a business rule failure from the Action; null lets the exception propagate. */
    protected ?string $businessErrorField = null;

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

    /**
     * Run a save callback and turn a business rule failure into a form error.
     *
     * @param  Closure(): void  $save
     */
    protected function reportBusinessErrors(Closure $save): bool
    {
        try {
            $save();
        } catch (BaseBusinessException $exception) {
            $this->addError($this->businessErrorField($exception), $exception->getMessage());

            return false;
        }

        return true;
    }

    /** The field that shows the failure; override to choose it from the exception. */
    protected function businessErrorField(BaseBusinessException $exception): string
    {
        return $this->businessErrorField ?? throw $exception;
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

    private function authorizeFormAccess(): void
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
