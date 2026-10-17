<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Components;

use App\Actions\Managers\ClearFromInjuryAction;
use App\Actions\Managers\EmployAction;
use App\Actions\Managers\InjureAction;
use App\Actions\Managers\ReinstateAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\RetireAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Concerns\ChecksIndividualLifecycleEligibility;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\Managers\Manager;
use App\Support\ModelKey;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Manager $manager
 */
class Actions extends Component
{
    use ChecksIndividualLifecycleEligibility;
    use ExecutesRosterActions;

    #[Locked]
    public int|string $managerId;

    /** The record route binding resolved for the mounting request; never persisted, so later requests reload it. */
    protected ?Manager $mountedManager = null;

    public function mount(Manager $manager): void
    {
        $this->managerId = ModelKey::of($manager);
        $this->mountedManager = $manager;
    }

    /**
     * Loaded from the locked id once per request, so every action authorizes the current row. Global scopes
     * (soft deletes, promotion) are skipped exactly as when Livewire restored the former model property: the
     * id was set at mount from a record that route binding had already resolved within them.
     */
    #[Computed]
    public function manager(): Manager
    {
        return $this->mountedManager ?? Manager::query()->withoutGlobalScopes()->findOrFail($this->managerId);
    }

    public function employ(EmployAction $employAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Employ, RosterEntityType::Manager, $this->manager, fn () => $employAction->handle($this->manager));
    }

    public function release(ReleaseAction $releaseAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Release, RosterEntityType::Manager, $this->manager, fn () => $releaseAction->handle($this->manager));
    }

    public function retire(RetireAction $retireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Retire, RosterEntityType::Manager, $this->manager, fn () => $retireAction->handle($this->manager));
    }

    public function unretire(UnretireAction $unretireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Unretire, RosterEntityType::Manager, $this->manager, fn () => $unretireAction->handle($this->manager));
    }

    public function suspend(SuspendAction $suspendAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Suspend, RosterEntityType::Manager, $this->manager, fn () => $suspendAction->handle($this->manager));
    }

    public function reinstate(ReinstateAction $reinstateAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Reinstate, RosterEntityType::Manager, $this->manager, fn () => $reinstateAction->handle($this->manager));
    }

    public function injure(InjureAction $injureAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Injure, RosterEntityType::Manager, $this->manager, fn () => $injureAction->handle($this->manager));
    }

    public function clearFromInjury(ClearFromInjuryAction $clearFromInjuryAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::ClearFromInjury, RosterEntityType::Manager, $this->manager, fn () => $clearFromInjuryAction->handle($this->manager));
    }

    public function canPerform(RosterLifecycleAction $action): bool
    {
        return Gate::allows($action->ability(), $this->manager)
            && $this->isEligibleFor($action, $this->manager);
    }

    public function render(): View
    {
        $this->loadLifecycleState($this->manager);

        return view('livewire.managers.components.actions');
    }
}
