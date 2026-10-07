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
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class Actions extends Component
{
    use ExecutesBusinessActions;

    public Stable $stable;

    public function mount(Stable $stable): void
    {
        $this->stable = $stable;
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
        $this->stable->refresh();
        $this->dispatch('stable-updated');
    }

    public function canPerform(StableLifecycleAction $action): bool
    {
        if (! Gate::allows($action->ability(), $this->stable)) {
            return false;
        }

        return match ($action) {
            StableLifecycleAction::Establish => app(StableActivityEligibility::class)->allows($this->stable, StableActivityTransition::Establish),
            StableLifecycleAction::Disband => app(StableActivityEligibility::class)->allows($this->stable, StableActivityTransition::Disband),
            StableLifecycleAction::Retire => app(StableRetirementEligibility::class)->canRetire($this->stable),
            StableLifecycleAction::Unretire => app(StableRetirementEligibility::class)->canUnretire($this->stable),
            StableLifecycleAction::Merge => app(StableRestructuringEligibility::class)->canStartMerge($this->stable),
            StableLifecycleAction::Split => app(StableRestructuringEligibility::class)->canSplit($this->stable),
            StableLifecycleAction::Reunite => app(StableActivityEligibility::class)->allows($this->stable, StableActivityTransition::Reunite),
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
        }
    }
}
