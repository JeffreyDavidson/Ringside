<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Components;

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;
use Closure;
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

        $this->executeTitleAction(
            function (): void {
                resolve(DebutAction::class)->handle($this->title);
            },
            'Title successfully debuted.',
        );
    }

    /**
     * Retire a title.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->title);

        $this->executeTitleAction(
            function (): void {
                resolve(RetireAction::class)->handle($this->title);
            },
            'Title successfully retired.',
        );
    }

    /**
     * Unretire a title.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->title);

        $this->executeTitleAction(
            function (): void {
                resolve(UnretireAction::class)->handle($this->title);
            },
            'Title successfully unretired.',
        );
    }

    /**
     * Pull a title.
     */
    public function deactivate(): void
    {
        Gate::authorize('pull', $this->title);

        $this->executeTitleAction(
            function (): void {
                resolve(PullAction::class)->handle($this->title);
            },
            'Title successfully pulled.',
        );
    }

    /**
     * Reinstate a title.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->title);

        $this->executeTitleAction(
            function (): void {
                resolve(ReinstateAction::class)->handle($this->title);
            },
            'Title successfully reinstated.',
        );
    }

    /**
     * Restore a deleted title.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->title);

        $this->executeTitleAction(
            function (): void {
                resolve(RestoreAction::class)->handle($this->title);
            },
            'Title successfully restored.',
        );
    }

    /** @param Closure(): void $action */
    private function executeTitleAction(Closure $action, string $successMessage): void
    {
        try {
            $action();

            $this->dispatch('title-updated');
            session()->flash('status', $successMessage);
        } catch (BaseBusinessException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.titles.components.actions');
    }
}
