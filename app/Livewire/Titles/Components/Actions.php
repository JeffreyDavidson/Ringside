<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Components;

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Builders\Titles\TitleBuilder;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Lifecycle\Titles\TitleLifecycleEligibility;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Models\Titles\Title;
use App\Support\ModelKey;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Title Actions Component
 *
 * Handles all business actions that can be performed on a title including
 * employment management, health status changes, and career lifecycle operations.
 * This component is designed to be reusable across different contexts (tables,
 * detail pages, cards, etc.) while maintaining consistent authorization and
 * error handling patterns.
 *
 * @property-read Title $title
 */
class Actions extends Component
{
    use ExecutesBusinessActions;

    private TitleLifecycleEligibility $lifecycleEligibility;

    #[Locked]
    public int|string $titleId;

    /** The record route binding resolved for the mounting request; never persisted, so later requests reload it. */
    protected ?Title $mountedTitle = null;

    public function boot(TitleLifecycleEligibility $lifecycleEligibility): void
    {
        $this->lifecycleEligibility = $lifecycleEligibility;
    }

    public function mount(Title $title): void
    {
        $this->titleId = ModelKey::of($title);
        $this->mountedTitle = $title;
    }

    /**
     * Loaded from the locked id once per request, so every action authorizes the current row. Global scopes
     * (soft deletes, promotion) are skipped exactly as when Livewire restored the former model property: the
     * id was set at mount from a record that route binding had already resolved within them.
     */
    #[Computed]
    public function title(): Title
    {
        return $this->mountedTitle ?? Title::query()->withoutGlobalScopes()->findOrFail($this->titleId);
    }

    public function debut(DebutAction $debutAction): void
    {
        $this->perform(TitleLifecycleTransition::Debut, fn () => $debutAction->handle($this->title), __('titles.actions.debuted'));
    }

    public function retire(RetireAction $retireAction): void
    {
        $this->perform(TitleLifecycleTransition::Retire, fn () => $retireAction->handle($this->title), __('titles.actions.retired'));
    }

    public function unretire(UnretireAction $unretireAction): void
    {
        $this->perform(TitleLifecycleTransition::Unretire, fn () => $unretireAction->handle($this->title), __('titles.actions.unretired'));
    }

    public function deactivate(PullAction $pullAction): void
    {
        $this->perform(TitleLifecycleTransition::Pull, fn () => $pullAction->handle($this->title), __('titles.actions.pulled'));
    }

    public function reinstate(ReinstateAction $reinstateAction): void
    {
        $this->perform(TitleLifecycleTransition::Reinstate, fn () => $reinstateAction->handle($this->title), __('titles.actions.reinstated'));
    }

    public function canPerform(TitleLifecycleTransition $transition): bool
    {
        return Gate::allows($transition->ability(), $this->title)
            && $this->lifecycleEligibility->allows($this->title, $transition);
    }

    public function render(): View
    {
        $this->title->loadExists(TitleBuilder::ACTIVITY_STATUS_STATE);

        return view('livewire.titles.components.actions');
    }

    /** @param Closure(): mixed $handler */
    private function perform(TitleLifecycleTransition $transition, Closure $handler, string $successMessage): void
    {
        Gate::authorize($transition->ability(), $this->title);

        if ($this->executeBusinessAction(function () use ($handler): void {
            $handler();
        }, $successMessage)) {
            $this->dispatch('title-updated');
        }
    }
}
