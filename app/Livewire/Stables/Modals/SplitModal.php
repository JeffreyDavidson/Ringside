<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Actions\Stables\SplitStableAction;
use App\Enums\Stables\StableMemberUnavailability;
use App\Exceptions\BaseBusinessException;
use App\Lifecycle\Roster\Stables\StableMembershipRequirements;
use App\Lifecycle\Roster\Stables\StableRestructuringEligibility;
use App\Livewire\Concerns\DispatchesActionFeedback;
use App\Livewire\Stables\Forms\SplitForm;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Stables\StableMembershipService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use LivewireUI\Modal\ModalComponent;

/**
 * Moves selected members of the stable being viewed into a new stable of the same promotion.
 *
 * @property-read Stable $stable
 * @property-read Collection<int, array{id: int, name: string, unavailability: StableMemberUnavailability|null}> $wrestlers
 * @property-read Collection<int, array{id: int, name: string, unavailability: StableMemberUnavailability|null}> $tagTeams
 */
class SplitModal extends ModalComponent
{
    use DispatchesActionFeedback;

    #[Locked]
    public int $stableId;

    public SplitForm $form;

    public function mount(int $stableId): void
    {
        $this->stableId = $stableId;

        Gate::authorize('split', $this->stable);
    }

    public function save(SplitStableAction $splitStableAction): void
    {
        $stable = $this->stable;
        Gate::authorize('split', $stable);

        $this->form->validateForSubmit();

        try {
            $newStable = $splitStableAction->handle(
                $stable,
                $this->form->name,
                $this->form->members(),
                now(),
            );
        } catch (BaseBusinessException $exception) {
            $this->addError('stable', $exception->getMessage());

            return;
        }

        $this->dispatchActionSuccess(__('stables.actions.split', ['name' => $stable->name, 'new' => $newStable->name]));
        $this->dispatch('stable-restructured');
        $this->dispatch('closeModal');
    }

    #[Computed]
    public function stable(): Stable
    {
        return Stable::query()->findOrFail($this->stableId);
    }

    /** @return Collection<int, array{id: int, name: string, unavailability: StableMemberUnavailability|null}> */
    #[Computed]
    public function wrestlers(): Collection
    {
        return $this->presentMembers(resolve(StableMembershipService::class)->currentMembers($this->stable)->wrestlers);
    }

    /** @return Collection<int, array{id: int, name: string, unavailability: StableMemberUnavailability|null}> */
    #[Computed]
    public function tagTeams(): Collection
    {
        return $this->presentMembers(resolve(StableMembershipService::class)->currentMembers($this->stable)->tagTeams);
    }

    public function minimumMemberCount(): int
    {
        return StableMembershipRequirements::MINIMUM_MEMBER_COUNT;
    }

    public function getModalTitle(): string
    {
        return __('stables.modals.split.title');
    }

    public function render(): View
    {
        return view('livewire.stables.modals.split-modal');
    }

    /**
     * @param  Collection<int, Wrestler>|Collection<int, TagTeam>|null  $members
     * @return Collection<int, array{id: int, name: string, unavailability: StableMemberUnavailability|null}>
     */
    private function presentMembers(?Collection $members): Collection
    {
        $eligibility = resolve(StableRestructuringEligibility::class);

        return ($members ?? collect())->map(fn (Wrestler|TagTeam $member): array => [
            'id' => $member->id,
            'name' => $member->name,
            'unavailability' => $eligibility->unavailabilityOf($member),
        ]);
    }
}
