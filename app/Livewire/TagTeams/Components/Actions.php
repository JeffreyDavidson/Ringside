<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Components;

use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReinstateAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Actions\TagTeams\RetireAction;
use App\Actions\TagTeams\SuspendAction;
use App\Actions\TagTeams\UnretireAction;
use App\Builders\Roster\TagTeamBuilder;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Lifecycle\Roster\TagTeams\TagTeamEmploymentEligibility;
use App\Lifecycle\Roster\TagTeams\TagTeamRetirementEligibility;
use App\Lifecycle\Roster\TagTeams\TagTeamSuspensionEligibility;
use App\Livewire\Concerns\DescribesUpcomingBookings;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\TagTeams\TagTeam;
use App\Support\ModelKey;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read TagTeam $tagTeam
 */
class Actions extends Component
{
    use DescribesUpcomingBookings;
    use ExecutesRosterActions;

    private TagTeamEmploymentEligibility $employmentEligibility;

    private TagTeamSuspensionEligibility $suspensionEligibility;

    private TagTeamRetirementEligibility $retirementEligibility;

    #[Locked]
    public int|string $tagTeamId;

    /** The record route binding resolved for the mounting request; never persisted, so later requests reload it. */
    protected ?TagTeam $mountedTagTeam = null;

    public function boot(
        TagTeamEmploymentEligibility $employmentEligibility,
        TagTeamSuspensionEligibility $suspensionEligibility,
        TagTeamRetirementEligibility $retirementEligibility,
    ): void {
        $this->employmentEligibility = $employmentEligibility;
        $this->suspensionEligibility = $suspensionEligibility;
        $this->retirementEligibility = $retirementEligibility;
    }

    public function mount(TagTeam $tagTeam): void
    {
        $this->tagTeamId = ModelKey::of($tagTeam);
        $this->mountedTagTeam = $tagTeam;
    }

    /**
     * Loaded from the locked id once per request, so every action authorizes the current row. Global scopes
     * (soft deletes, promotion) are skipped exactly as when Livewire restored the former model property: the
     * id was set at mount from a record that route binding had already resolved within them.
     */
    #[Computed]
    public function tagTeam(): TagTeam
    {
        return $this->mountedTagTeam ?? TagTeam::query()->withoutGlobalScopes()->findOrFail($this->tagTeamId);
    }

    public function employ(EmployAction $employAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Employ, RosterEntityType::TagTeam, $this->tagTeam, fn () => $employAction->handle($this->tagTeam));
    }

    public function release(ReleaseAction $releaseAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Release, RosterEntityType::TagTeam, $this->tagTeam, fn () => $releaseAction->handle($this->tagTeam));
    }

    public function retire(RetireAction $retireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Retire, RosterEntityType::TagTeam, $this->tagTeam, fn () => $retireAction->handle($this->tagTeam));
    }

    public function unretire(UnretireAction $unretireAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Unretire, RosterEntityType::TagTeam, $this->tagTeam, fn () => $unretireAction->handle($this->tagTeam));
    }

    public function suspend(SuspendAction $suspendAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Suspend, RosterEntityType::TagTeam, $this->tagTeam, fn () => $suspendAction->handle($this->tagTeam));
    }

    public function reinstate(ReinstateAction $reinstateAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Reinstate, RosterEntityType::TagTeam, $this->tagTeam, fn () => $reinstateAction->handle($this->tagTeam));
    }

    public function canPerform(RosterLifecycleAction $action): bool
    {
        if (! Gate::allows($action->ability(), $this->tagTeam)) {
            return false;
        }

        return match ($action) {
            RosterLifecycleAction::Employ => $this->employmentEligibility->canEmploy($this->tagTeam),
            RosterLifecycleAction::Release => $this->employmentEligibility->canRelease($this->tagTeam),
            RosterLifecycleAction::Suspend => $this->suspensionEligibility->canSuspend($this->tagTeam),
            RosterLifecycleAction::Reinstate => $this->suspensionEligibility->canReinstate($this->tagTeam),
            RosterLifecycleAction::Retire => $this->retirementEligibility->canRetire($this->tagTeam),
            RosterLifecycleAction::Unretire => $this->retirementEligibility->canUnretire($this->tagTeam),
            default => false,
        };
    }

    public function render(): View
    {
        $this->tagTeam->loadExists(TagTeamBuilder::ROSTER_STATE);

        $showsBookingWarning = $this->canPerform(RosterLifecycleAction::Release)
            || $this->canPerform(RosterLifecycleAction::Retire);

        return view('livewire.tag-teams.components.actions', [
            'bookedEvents' => $showsBookingWarning ? $this->upcomingBookingSummary($this->tagTeam) : '',
        ]);
    }
}
