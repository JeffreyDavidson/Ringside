<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Modals;

use App\Actions\Titles\CreateAction;
use App\Actions\Titles\UpdateAction;
use App\Enums\Titles\TitleType;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Titles\Forms\CreateEditForm;
use App\Models\Titles\Title;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

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

    protected function populateDummyData(): void
    {
        $this->form->name = Str::of(fake()->word().' '.fake()->word())->title()->append(' Title')->value();
        $this->form->type = fake()->boolean()
            ? TitleType::Singles->value
            : TitleType::TagTeam->value;
        $this->form->start_date = $this->generateOptionalStartDate('Y-m-d', 0.6, '-1 year', 'now');
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit Title';
        }

        return 'Create Title';
    }

    /** @return array<string, string> */
    #[Computed]
    public function getTitleTypes(): array
    {
        return TitleType::options();
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->title(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    public function render(): View
    {
        return view('livewire.titles.modals.form-modal');
    }
}
