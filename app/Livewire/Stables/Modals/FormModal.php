<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Actions\Stables\CreateAction;
use App\Actions\Stables\UpdateAction;
use App\Enums\Roster\RosterMemberKind;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\SearchesRosterMembers;
use App\Livewire\Stables\Forms\CreateEditForm;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

/**
 * @extends BaseFormModal<CreateEditForm, Stable>
 *
 * @property-read array<string, array<int, array{id: int|string, name: string}>> $selectedRosterLabels
 */
class FormModal extends BaseFormModal
{
    use SearchesRosterMembers;

    #[\Override]
    protected ?string $businessErrorField = 'form.started_at';

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
        return Stable::class;
    }

    protected function populateDummyData(): void
    {
        $this->form->name = Str::of(fake()->sentence(2))->title()->value();
        $this->form->started_at = $this->generateOptionalStartDate();
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->stable(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    /** @return array<int, RosterMemberKind> */
    protected function searchableRosterKinds(): array
    {
        return [RosterMemberKind::Wrestlers, RosterMemberKind::TagTeams];
    }

    /**
     * Names for the ids already chosen in the form, so editing shows them whatever the search returns.
     *
     * @return array<string, array<int, array{id: int|string, name: string}>>
     */
    #[Computed]
    public function selectedRosterLabels(): array
    {
        return [
            RosterMemberKind::Wrestlers->value => $this->rosterLabels(RosterMemberKind::Wrestlers, $this->form->wrestlers),
            RosterMemberKind::TagTeams->value => $this->rosterLabels(RosterMemberKind::TagTeams, $this->form->tag_teams),
        ];
    }

    public function render(): View
    {
        return view('livewire.stables.modals.form-modal');
    }
}
