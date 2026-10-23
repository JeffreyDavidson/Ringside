<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Modals;

use App\Actions\Wrestlers\CreateAction;
use App\Actions\Wrestlers\UpdateAction;
use App\Enums\Roster\RosterMemberKind;
use App\Enums\Shared\BusinessRuleReason;
use App\Enums\Shared\UnitedStatesState;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Roster\Managers\CannotBeAssignedException;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\SearchesRosterMembers;
use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Override;

/**
 * @extends BaseFormModal<CreateEditForm, Wrestler>
 *
 * @property-read array<string, array<int, array{id: int|string, name: string}>> $selectedRosterLabels
 */
class FormModal extends BaseFormModal
{
    use SearchesRosterMembers;

    #[Override]
    protected ?string $businessErrorField = 'form.name';

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

    /**
     * A signature move taken since validation shows on its own field, and so does a manager who stopped being
     * available; any other failure on the name.
     */
    #[Override]
    protected function businessErrorField(BaseBusinessException $exception): string
    {
        if ($exception instanceof CannotBeAssignedException) {
            return 'form.managers';
        }

        return match ($exception->reason()) {
            BusinessRuleReason::SignatureMoveTaken => 'form.signature_move',
            default => parent::businessErrorField($exception),
        };
    }

    /** @return array<int, RosterMemberKind> */
    protected function searchableRosterKinds(): array
    {
        return [RosterMemberKind::Managers];
    }

    /**
     * Names for the managers already chosen in the form, so editing shows them whatever the search returns.
     *
     * @return array<string, array<int, array{id: int|string, name: string}>>
     */
    #[Computed]
    public function selectedRosterLabels(): array
    {
        return [
            RosterMemberKind::Managers->value => $this->rosterLabels(RosterMemberKind::Managers, $this->form->managers),
        ];
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->wrestler(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData(), $this->form->formPromotionId());
    }

    public function render(): View
    {
        return view('livewire.wrestlers.modals.form-modal');
    }
}
