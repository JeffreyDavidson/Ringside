<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Modals;

use App\Actions\Wrestlers\CreateAction;
use App\Actions\Wrestlers\UpdateAction;
use App\Enums\Shared\UnitedStatesState;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;
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

    protected function populateDummyData(): void
    {
        $this->form->name = Str::of(fake()->sentence(2))->title()->value();
        $this->form->hometown = fake()->city().', '.Collection::make(UnitedStatesState::cases())->random()->value;
        $this->form->height_feet = fake()->numberBetween(5, 7);
        $this->form->height_inches = fake()->numberBetween(0, 11);
        $this->form->weight = fake()->numberBetween(180, 350);
        $this->form->signature_move = Str::of(fake()->optional(0.8)->sentence(3))->title()->value();
        $this->form->employment_date = $this->generateOptionalEmploymentDate();
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->wrestler(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    public function render(): View
    {
        return view('livewire.wrestlers.modals.form-modal');
    }
}
