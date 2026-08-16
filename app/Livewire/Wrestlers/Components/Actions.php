<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Components;

use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\HealAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RestoreAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Enums\Roster\RosterEntityType;
use App\Livewire\Concerns\ExecutesRosterActions;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Actions extends Component
{
    use ExecutesRosterActions;

    public Wrestler $wrestler;

    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    public function employ(): void
    {
        Gate::authorize('employ', $this->wrestler);
        $this->executeRosterAction('employed', RosterEntityType::Wrestler, fn () => resolve(EmployAction::class)->handle($this->wrestler));
    }

    public function release(): void
    {
        Gate::authorize('release', $this->wrestler);
        $this->executeRosterAction('released', RosterEntityType::Wrestler, fn () => resolve(ReleaseAction::class)->handle($this->wrestler));
    }

    public function retire(): void
    {
        Gate::authorize('retire', $this->wrestler);
        $this->executeRosterAction('retired', RosterEntityType::Wrestler, fn () => resolve(RetireAction::class)->handle($this->wrestler));
    }

    public function unretire(): void
    {
        Gate::authorize('unretire', $this->wrestler);
        $this->executeRosterAction('unretired', RosterEntityType::Wrestler, fn () => resolve(UnretireAction::class)->handle($this->wrestler));
    }

    public function suspend(): void
    {
        Gate::authorize('suspend', $this->wrestler);
        $this->executeRosterAction('suspended', RosterEntityType::Wrestler, fn () => resolve(SuspendAction::class)->handle($this->wrestler));
    }

    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->wrestler);
        $this->executeRosterAction('reinstated', RosterEntityType::Wrestler, fn () => resolve(ReinstateAction::class)->handle($this->wrestler));
    }

    public function injure(): void
    {
        Gate::authorize('injure', $this->wrestler);
        $this->executeRosterAction('injured', RosterEntityType::Wrestler, fn () => resolve(InjureAction::class)->handle($this->wrestler));
    }

    public function healFromInjury(): void
    {
        Gate::authorize('clearFromInjury', $this->wrestler);
        $this->executeRosterAction('healed', RosterEntityType::Wrestler, fn () => resolve(HealAction::class)->handle($this->wrestler));
    }

    public function restore(): void
    {
        Gate::authorize('restore', $this->wrestler);
        $this->executeRosterAction('restored', RosterEntityType::Wrestler, fn () => resolve(RestoreAction::class)->handle($this->wrestler));
    }

    public function render(): View
    {
        return view('livewire.wrestlers.components.actions');
    }
}
