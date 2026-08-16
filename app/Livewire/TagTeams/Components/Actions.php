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

    public function employ(): void
    {
        Gate::authorize('employ', $this->tagTeam);
        $this->executeRosterAction('employed', RosterEntityType::TagTeam, fn () => resolve(EmployAction::class)->handle($this->tagTeam));
    }

    public function release(): void
    {
        Gate::authorize('release', $this->tagTeam);
        $this->executeRosterAction('released', RosterEntityType::TagTeam, fn () => resolve(ReleaseAction::class)->handle($this->tagTeam));
    }

    public function retire(): void
    {
        Gate::authorize('retire', $this->tagTeam);
        $this->executeRosterAction('retired', RosterEntityType::TagTeam, fn () => resolve(RetireAction::class)->handle($this->tagTeam));
    }

    public function unretire(): void
    {
        Gate::authorize('unretire', $this->tagTeam);
        $this->executeRosterAction('unretired', RosterEntityType::TagTeam, fn () => resolve(UnretireAction::class)->handle($this->tagTeam));
    }

    public function suspend(): void
    {
        Gate::authorize('suspend', $this->tagTeam);
        $this->executeRosterAction('suspended', RosterEntityType::TagTeam, fn () => resolve(SuspendAction::class)->handle($this->tagTeam));
    }

    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->tagTeam);
        $this->executeRosterAction('reinstated', RosterEntityType::TagTeam, fn () => resolve(ReinstateAction::class)->handle($this->tagTeam));
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->tagTeam);
        $this->executeRosterAction('deleted', RosterEntityType::TagTeam, fn () => resolve(DeleteAction::class)->handle($this->tagTeam));
    }

    public function restore(): void
    {
        Gate::authorize('restore', $this->tagTeam);
        $this->executeRosterAction('restored', RosterEntityType::TagTeam, fn () => resolve(RestoreAction::class)->handle($this->tagTeam));
    }

    public function render(): View
    {
        return view('livewire.tag-teams.components.actions');
    }
}
