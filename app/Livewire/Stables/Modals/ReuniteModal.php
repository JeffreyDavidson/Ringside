<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Actions\Stables\ReuniteAction;
use App\Data\Stables\StableMembershipData;
use App\Exceptions\BaseBusinessException;
use App\Lifecycle\Roster\Stables\StableFormerMemberEligibility;
use App\Lifecycle\Roster\Stables\StableMembershipRequirements;
use App\Livewire\Concerns\DispatchesActionFeedback;
use App\Livewire\Stables\Forms\ReuniteForm;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use LivewireUI\Modal\ModalComponent;

/**
 * Starts a new activity period for the stable being viewed and brings back the chosen former members.
 *
 * @property-read Stable $stable
 * @property-read StableMembershipData $formerMembers
 * @property-read Collection<int, Wrestler> $wrestlers
 * @property-read Collection<int, TagTeam> $tagTeams
 */
class ReuniteModal extends ModalComponent
{
    use DispatchesActionFeedback;

    #[Locked]
    public int $stableId;

    public ReuniteForm $form;

    public function mount(int $stableId): void
    {
        $this->stableId = $stableId;

        Gate::authorize('reunite', $this->stable);

        $this->form->selectAll($this->formerMembers);
    }

    public function save(ReuniteAction $reuniteAction): void
    {
        $stable = $this->stable;
        Gate::authorize('reunite', $stable);

        $this->form->validateForSubmit();

        try {
            $reuniteAction->handle(
                $stable,
                $this->form->members(),
                now(),
            );
        } catch (BaseBusinessException $exception) {
            $this->addError('stable', $exception->getMessage());

            return;
        }

        $this->dispatchActionSuccess(__('stables.actions.reunited', ['name' => $stable->name]));
        $this->dispatch('stable-restructured');
        $this->dispatch('closeModal');
    }

    #[Computed]
    public function stable(): Stable
    {
        return Stable::query()->findOrFail($this->stableId);
    }

    #[Computed]
    public function formerMembers(): StableMembershipData
    {
        return resolve(StableFormerMemberEligibility::class)->availableMembersFor($this->stable);
    }

    /** @return Collection<int, Wrestler> */
    #[Computed]
    public function wrestlers(): Collection
    {
        return $this->formerMembers->wrestlers ?? collect();
    }

    /** @return Collection<int, TagTeam> */
    #[Computed]
    public function tagTeams(): Collection
    {
        return $this->formerMembers->tagTeams ?? collect();
    }

    public function minimumMemberCount(): int
    {
        return StableMembershipRequirements::MINIMUM_MEMBER_COUNT;
    }

    public function getModalTitle(): string
    {
        return __('stables.modals.reunite.title');
    }

    public function render(): View
    {
        return view('livewire.stables.modals.reunite-modal');
    }
}
