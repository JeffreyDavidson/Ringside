<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Components;

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Exceptions\Status\CannotBeDebutedException;
use App\Exceptions\Status\CannotBePulledException;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Exceptions\Status\CannotBeUnretiredException;
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
class TitleActionsComponent extends Component
{
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

        try {
            resolve(DebutAction::class)->handle($this->title);
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully debuted.');
        } catch (CannotBeDebutedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Retire a title.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->title);

        try {
            resolve(RetireAction::class)->handle($this->title);
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully retired.');
        } catch (CannotBeRetiredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Unretire a title.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->title);

        try {
            resolve(UnretireAction::class)->handle($this->title);
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully unretired.');
        } catch (CannotBeUnretiredException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Pull a title.
     */
    public function deactivate(): void
    {
        Gate::authorize('pull', $this->title);

        try {
            resolve(PullAction::class)->handle($this->title);
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully pulled.');
        } catch (CannotBePulledException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Reinstate a title.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->title);

        try {
            resolve(ReinstateAction::class)->handle($this->title);
            $this->dispatch('title-updated');
            session()->flash('status', 'Title successfully reinstated.');
        } catch (CannotBeReinstatedException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Restore a deleted title.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->title);

        resolve(RestoreAction::class)->handle($this->title);
        $this->dispatch('title-updated');
        session()->flash('status', 'Title successfully restored.');
    }

    public function render(): View
    {
        return view('livewire.titles.components.title-actions-component');
    }
}
