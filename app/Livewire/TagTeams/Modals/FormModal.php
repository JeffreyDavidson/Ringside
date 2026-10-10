<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Modals;

use App\Actions\TagTeams\CreateAction;
use App\Actions\TagTeams\UpdateAction;
use App\Enums\BusinessRuleReason;
use App\Enums\Roster\RosterMemberKind;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\SearchesRosterMembers;
use App\Livewire\TagTeams\Forms\CreateEditForm;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

/**
 * @extends BaseFormModal<CreateEditForm, TagTeam>
 *
 * @property-read array<string, array<int, array{id: int|string, name: string}>> $selectedRosterLabels
 */
class FormModal extends BaseFormModal
{
    use SearchesRosterMembers;

    #[\Override]
    protected ?string $businessErrorField = 'form.wrestlerA';

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
        return TagTeam::class;
    }

    protected function populateDummyData(): void
    {
        $wrestlers = Wrestler::query()
            ->inRandomOrder()
            ->limit(2)
            ->get(['id']);

        $this->form->name = Str::of(fake()->sentence(2))->title()->value();
        $this->form->signature_move = Str::of(fake()->optional(0.8)->sentence(3))->title()->value();
        $this->form->employment_date = $this->generateOptionalEmploymentDate();
        $this->form->wrestlerA = $wrestlers->get(0)?->id;
        $this->form->wrestlerB = $wrestlers->get(1)?->id;
    }

    /** A name or signature move taken since validation shows on its own field; any other failure on the first wrestler. */
    #[\Override]
    protected function businessErrorField(BaseBusinessException $exception): string
    {
        return match ($exception->reason()) {
            BusinessRuleReason::NameTaken => 'form.name',
            BusinessRuleReason::SignatureMoveTaken => 'form.signature_move',
            default => parent::businessErrorField($exception),
        };
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->tagTeam(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    /** @return array<int, RosterMemberKind> */
    protected function searchableRosterKinds(): array
    {
        return [RosterMemberKind::Wrestlers, RosterMemberKind::Managers];
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
            RosterMemberKind::Wrestlers->value => $this->rosterLabels(
                RosterMemberKind::Wrestlers,
                [$this->form->wrestlerA, $this->form->wrestlerB],
            ),
            RosterMemberKind::Managers->value => $this->rosterLabels(
                RosterMemberKind::Managers,
                $this->form->managers,
            ),
        ];
    }

    public function render(): View
    {
        return view('livewire.tag-teams.modals.form-modal');
    }
}
