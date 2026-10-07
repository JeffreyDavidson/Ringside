<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Modals;

use App\Actions\Stables\MergeStablesAction;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\DispatchesActionFeedback;
use App\Livewire\Stables\Forms\MergeForm;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use LivewireUI\Modal\ModalComponent;

/**
 * Merges another active stable of the same promotion into the stable being viewed.
 *
 * A plain select lists the candidates because roster-combobox only searches wrestlers, tag teams and referees,
 * and a promotion has few active stables.
 *
 * @property-read Stable $stable
 * @property-read Collection<int, Stable> $candidates
 * @property-read Stable|null $selectedStable
 */
class MergeModal extends ModalComponent
{
    use DispatchesActionFeedback;

    #[Locked]
    public int $stableId;

    public MergeForm $form;

    public function mount(int $stableId): void
    {
        $this->stableId = $stableId;

        Gate::authorize('merge', $this->stable);
    }

    public function save(MergeStablesAction $mergeStablesAction): void
    {
        $stable = $this->stable;
        Gate::authorize('merge', $stable);

        $this->form->validateFor($this->candidates->modelKeys());

        $otherStable = $this->candidates->firstOrFail('id', $this->form->otherStableId);
        Gate::authorize('merge', $otherStable);

        try {
            $mergeStablesAction->handle($stable, $otherStable, now());
        } catch (BaseBusinessException $exception) {
            $this->addError('stable', $exception->getMessage());

            return;
        }

        $this->dispatchActionSuccess(__('stables.actions.merged', ['other' => $otherStable->name, 'name' => $stable->name]));
        $this->dispatch('stable-restructured');
        $this->dispatch('closeModal');
    }

    #[Computed]
    public function stable(): Stable
    {
        return Stable::query()->findOrFail($this->stableId);
    }

    /**
     * Active, unretired stables of the same promotion, which administrators would otherwise see across promotions.
     *
     * @return Collection<int, Stable>
     */
    #[Computed]
    public function candidates(): Collection
    {
        return Stable::query()
            ->mergeCandidatesFor($this->stable)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function selectedStable(): ?Stable
    {
        return $this->candidates->firstWhere('id', $this->form->otherStableId);
    }

    public function getModalTitle(): string
    {
        return __('stables.modals.merge.title');
    }

    public function render(): View
    {
        return view('livewire.stables.modals.merge-modal');
    }
}
