<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Modals;

use App\Actions\Wrestlers\CreateAction;
use App\Actions\Wrestlers\UpdateAction;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Wrestler>
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
        return Wrestler::class;
    }

    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn () => Str::of(fake()->sentence(2))->title()->value(),
            'hometown' => fn (): string => fake()->city().', '.fake()->stateAbbr(), // @phpstan-ignore-line
            'height_feet' => fn (): int => fake()->numberBetween(5, 7),
            'height_inches' => fn (): int => fake()->numberBetween(0, 11),
            'weight' => fn (): int => fake()->numberBetween(180, 350),
            'signature_move' => fn () => Str::of(fake()->optional(0.8)->sentence(3))->title()->value(),
            'employment_date' => fn (): ?string => $this->generateOptionalEmploymentDate(),
        ];
    }

    protected function storeForm(): bool
    {
        $this->form->validate();

        if ($this->form->isEditing()) {
            $this->updateAction->handle($this->form->wrestler(), $this->form->toData());

            return true;
        }

        $this->createAction->handle($this->form->toData());

        return true;
    }

    public function render(): View
    {
        return view('livewire.wrestlers.modals.form-modal');
    }
}
