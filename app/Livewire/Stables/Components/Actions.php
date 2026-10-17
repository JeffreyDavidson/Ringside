<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Components;

use App\Actions\Stables\DisbandAction;
use App\Actions\Stables\EstablishAction;
use App\Actions\Stables\RetireAction;
use App\Actions\Stables\UnretireAction;
use App\Builders\Roster\StableBuilder;
use App\Enums\Stables\StableActivityTransition;
use App\Enums\Stables\StableLifecycleAction;
use App\Lifecycle\Roster\Stables\StableActivityEligibility;
use App\Lifecycle\Roster\Stables\StableRestructuringEligibility;
use App\Lifecycle\Roster\Stables\StableRetirementEligibility;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use App\Support\ModelKey;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Stable $stable
 */
class Actions extends Component
{
    use ExecutesBusinessActions;

    private StableActivityEligibility $activityEligibility;

    private StableRetirementEligibility $retirementEligibility;

    private StableRestructuringEligibility $restructuringEligibility;

    #[Locked]
    public int|string $stableId;

    /** The record route binding resolved for the mounting request; never persisted, so later requests reload it. */
    protected ?Stable $mountedStable = null;

    public function boot(
        StableActivityEligibility $activityEligibility,
        StableRetirementEligibility $retirementEligibility,
        StableRestructuringEligibility $restructuringEligibility,
    ): void {
        $this->activityEligibility = $activityEligibility;
        $this->retirementEligibility = $retirementEligibility;
        $this->restructuringEligibility = $restructuringEligibility;
    }

    public function mount(Stable $stable): void
    {
        $this->stableId = ModelKey::of($stable);
        $this->mountedStable = $stable;
    }

    /**
     * Loaded from the locked id once per request, so every action authorizes the current row. Global scopes
     * (soft deletes, promotion) are skipped exactly as when Livewire restored the former model property: the
     * id was set at mount from a record that route binding had already resolved within them.
     */
    #[Computed]
    public function stable(): Stable
    {
        return $this->mountedStable ?? Stable::query()->withoutGlobalScopes()->findOrFail($this->stableId);
    }

    public function establish(EstablishAction $establishAction): void
    {
        $this->perform(StableLifecycleAction::Establish, fn (): ActivityPeriod => $establishAction->handle($this->stable), __('stables.actions.established'));
    }

    public function disband(DisbandAction $disbandAction): void
    {
        $this->perform(StableLifecycleAction::Disband, fn () => $disbandAction->handle($this->stable), __('stables.actions.disbanded'));
    }

    public function retire(RetireAction $retireAction): void
    {
        $this->perform(StableLifecycleAction::Retire, fn () => $retireAction->handle($this->stable), __('stables.actions.retired'));
    }

    public function unretire(UnretireAction $unretireAction): void
    {
        $this->perform(StableLifecycleAction::Unretire, fn () => $unretireAction->handle($this->stable), __('stables.actions.unretired'));
    }

    public function merge(): void
    {
        $this->openModal(StableLifecycleAction::Merge, 'stables.modals.merge-modal');
    }

    public function split(): void
    {
        $this->openModal(StableLifecycleAction::Split, 'stables.modals.split-modal');
    }

    public function reunite(): void
    {
        $this->openModal(StableLifecycleAction::Reunite, 'stables.modals.reunite-modal');
    }

    #[On('stable-restructured')]
    public function refreshAfterRestructuring(): void
    {
        unset($this->stable);
        $this->dispatch('stable-updated');
        $this->dispatch('refreshDatatable');
    }

    public function canPerform(StableLifecycleAction $action): bool
    {
        if (! Gate::allows($action->ability(), $this->stable)) {
            return false;
        }

        return match ($action) {
            StableLifecycleAction::Establish => $this->activityEligibility->allows($this->stable, StableActivityTransition::Establish),
            StableLifecycleAction::Disband => $this->activityEligibility->allows($this->stable, StableActivityTransition::Disband),
            StableLifecycleAction::Retire => $this->retirementEligibility->canRetire($this->stable),
            StableLifecycleAction::Unretire => $this->retirementEligibility->canUnretire($this->stable),
            StableLifecycleAction::Merge => $this->restructuringEligibility->canStartMerge($this->stable)
                && $this->restructuringEligibility->hasMergeCandidate($this->stable),
            StableLifecycleAction::Split => $this->restructuringEligibility->canSplit($this->stable),
            StableLifecycleAction::Reunite => $this->activityEligibility->allows($this->stable, StableActivityTransition::Reunite),
        };
    }

    public function render(): View
    {
        $this->stable->loadExists(StableBuilder::ACTIVITY_STATUS_STATE);

        return view('livewire.stables.components.actions');
    }

    private function openModal(StableLifecycleAction $action, string $component): void
    {
        Gate::authorize($action->ability(), $this->stable);

        $this->dispatch('openModal', $component, ['stableId' => $this->stable->getKey()]);
    }

    /** @param Closure(): mixed $handler */
    private function perform(StableLifecycleAction $action, Closure $handler, string $successMessage): void
    {
        Gate::authorize($action->ability(), $this->stable);

        if ($this->executeBusinessAction(function () use ($handler): void {
            $handler();
        }, $successMessage)) {
            $this->dispatch('stable-updated');
            $this->dispatch('refreshDatatable');
        }
    }
}
