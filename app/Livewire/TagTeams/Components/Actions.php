<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Components;

use App\Actions\TagTeams\DeleteAction;
use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReinstateAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Actions\TagTeams\RestoreAction;
use App\Actions\TagTeams\RetireAction;
use App\Actions\TagTeams\SuspendAction;
use App\Actions\TagTeams\UnretireAction;
use App\Enums\Roster\RosterEntityType;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Actions extends Component
{
    use ExecutesRosterActions;

    public TagTeam $tagTeam;

    public function mount(TagTeam $tagTeam): void
    {
        $this->tagTeam = $tagTeam;
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

    public function delete(DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $this->tagTeam);
        $this->executeRosterAction('deleted', RosterEntityType::TagTeam, fn () => $deleteAction->handle($this->tagTeam));
    }

    public function restore(RestoreAction $restoreAction): void
    {
        $this->executeAuthorizedRosterAction(RosterLifecycleAction::Restore, RosterEntityType::TagTeam, $this->tagTeam, fn () => $restoreAction->handle($this->tagTeam));
    }

    public function render(): View
    {
        return view('livewire.tag-teams.components.actions');
    }
}
