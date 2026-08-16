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
        $this->executeRosterAction('employed', 'tag-team', fn () => resolve(EmployAction::class)->handle($this->tagTeam));
    }

    public function release(): void
    {
        Gate::authorize('release', $this->tagTeam);
        $this->executeRosterAction('released', 'tag-team', fn () => resolve(ReleaseAction::class)->handle($this->tagTeam));
    }

    public function retire(): void
    {
        Gate::authorize('retire', $this->tagTeam);
        $this->executeRosterAction('retired', 'tag-team', fn () => resolve(RetireAction::class)->handle($this->tagTeam));
    }

    public function unretire(): void
    {
        Gate::authorize('unretire', $this->tagTeam);
        $this->executeRosterAction('unretired', 'tag-team', fn () => resolve(UnretireAction::class)->handle($this->tagTeam));
    }

    public function suspend(): void
    {
        Gate::authorize('suspend', $this->tagTeam);
        $this->executeRosterAction('suspended', 'tag-team', fn () => resolve(SuspendAction::class)->handle($this->tagTeam));
    }

    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->tagTeam);
        $this->executeRosterAction('reinstated', 'tag-team', fn () => resolve(ReinstateAction::class)->handle($this->tagTeam));
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->tagTeam);
        $this->executeRosterAction('deleted', 'tag-team', fn () => resolve(DeleteAction::class)->handle($this->tagTeam));
    }

    public function restore(): void
    {
        Gate::authorize('restore', $this->tagTeam);
        $this->executeRosterAction('restored', 'tag-team', fn () => resolve(RestoreAction::class)->handle($this->tagTeam));
    }

    public function render(): View
    {
        return view('livewire.tag-teams.components.actions');
    }
}
