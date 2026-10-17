<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Components;

use App\Actions\Wrestlers\ClearFromInjuryAction;
use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Concerns\ChecksIndividualLifecycleEligibility;
use App\Livewire\Concerns\DescribesUpcomingBookings;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Support\ModelKey;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Wrestler $wrestler
 */
class Actions extends Component
{
    use ChecksIndividualLifecycleEligibility;
    use DescribesUpcomingBookings;
    use ExecutesRosterActions;

    #[Locked]
    public int|string $wrestlerId;

    /** The record route binding resolved for the mounting request; never persisted, so later requests reload it. */
    protected ?Wrestler $mountedWrestler = null;

    public function mount(Wrestler $wrestler): void
    {
        $this->wrestlerId = ModelKey::of($wrestler);
        $this->mountedWrestler = $wrestler;
    }

    /**
     * Loaded from the locked id once per request, so every action authorizes the current row. Global scopes
     * (soft deletes, promotion) are skipped exactly as when Livewire restored the former model property: the
     * id was set at mount from a record that route binding had already resolved within them.
     */
    #[Computed]
    public function wrestler(): Wrestler
    {
        return $this->mountedWrestler ?? Wrestler::query()->withoutGlobalScopes()->findOrFail($this->wrestlerId);
    }

    public function employ(EmployAction $employAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Employ, RosterEntityType::Wrestler, $this->wrestler, fn () => $employAction->handle($this->wrestler));
    }

    public function release(ReleaseAction $releaseAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Release, RosterEntityType::Wrestler, $this->wrestler, fn () => $releaseAction->handle($this->wrestler));
    }

    public function retire(RetireAction $retireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Retire, RosterEntityType::Wrestler, $this->wrestler, fn () => $retireAction->handle($this->wrestler));
    }

    public function unretire(UnretireAction $unretireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Unretire, RosterEntityType::Wrestler, $this->wrestler, fn () => $unretireAction->handle($this->wrestler));
    }

    public function suspend(SuspendAction $suspendAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Suspend, RosterEntityType::Wrestler, $this->wrestler, fn () => $suspendAction->handle($this->wrestler));
    }

    public function reinstate(ReinstateAction $reinstateAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Reinstate, RosterEntityType::Wrestler, $this->wrestler, fn () => $reinstateAction->handle($this->wrestler));
    }

    public function injure(InjureAction $injureAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Injure, RosterEntityType::Wrestler, $this->wrestler, fn () => $injureAction->handle($this->wrestler));
    }

    public function clearFromInjury(ClearFromInjuryAction $clearFromInjuryAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::ClearFromInjury, RosterEntityType::Wrestler, $this->wrestler, fn () => $clearFromInjuryAction->handle($this->wrestler));
    }

    public function canPerform(RosterLifecycleAction $action): bool
    {
        return Gate::allows($action->ability(), $this->wrestler)
            && $this->isEligibleFor($action, $this->wrestler);
    }

    public function render(): View
    {
        $this->loadLifecycleState($this->wrestler);

        $showsBookingWarning = $this->canPerform(RosterLifecycleAction::Release)
            || $this->canPerform(RosterLifecycleAction::Retire);

        return view('livewire.wrestlers.components.actions', [
            'bookedEvents' => $showsBookingWarning ? $this->upcomingBookingSummary($this->wrestler) : '',
        ]);
    }
}
