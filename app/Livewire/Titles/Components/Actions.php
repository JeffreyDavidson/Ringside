<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Components;

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Livewire\Concerns\ExecutesTitleActions;
use App\Models\Titles\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Title Actions Component
 *
 * Handles all business actions that can be performed on a title including
 * employment management, health status changes, and career lifecycle operations.
 * This component is designed to be reusable across different contexts (tables,
 * detail pages, cards, etc.) while maintaining consistent authorization and
 * error handling patterns.
 */
class Actions extends Component
{
    use ExecutesTitleActions;

    public Title $title;

    public function mount(Title $title): void
    {
        $this->title = $title;
    }

    /**
     * Employ a title.
     */
    public function debut(): void
    {
        Gate::authorize('debut', $this->title);

        if ($this->executeTitleAction(
            function (): void {
                resolve(DebutAction::class)->handle($this->title);
            },
        )) {
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully debuted.');
        }
    }

    /**
     * Retire a title.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->title);

        if ($this->executeTitleAction(
            function (): void {
                resolve(RetireAction::class)->handle($this->title);
            },
        )) {
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully retired.');
        }
    }

    /**
     * Unretire a title.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->title);

        if ($this->executeTitleAction(
            function (): void {
                resolve(UnretireAction::class)->handle($this->title);
            },
        )) {
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully unretired.');
        }
    }

    /**
     * Pull a title.
     */
    public function deactivate(): void
    {
        Gate::authorize('pull', $this->title);

        if ($this->executeTitleAction(
            function (): void {
                resolve(PullAction::class)->handle($this->title);
            },
        )) {
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully pulled.');
        }
    }

    /**
     * Reinstate a title.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->title);

        if ($this->executeTitleAction(
            function (): void {
                resolve(ReinstateAction::class)->handle($this->title);
            },
        )) {
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully reinstated.');
        }
    }

    /**
     * Restore a deleted title.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->title);

        if ($this->executeTitleAction(
            function (): void {
                resolve(RestoreAction::class)->handle($this->title);
            },
        )) {
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully restored.');
        }
    }

    public function render(): View
    {
        return view('livewire.titles.components.actions');
    }
}
