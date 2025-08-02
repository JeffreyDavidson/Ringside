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
use App\Livewire\Concerns\ExecutesActionsWithContext;
use App\Models\TagTeams\TagTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Tag Team Actions Component
 *
 * Handles all business actions that can be performed on a tag team including
 * employment management, lifecycle operations, and partnership management.
 * This component is designed to be reusable across different contexts (tables,
 * detail pages, cards, etc.) while maintaining consistent authorization and
 * error handling patterns.
 */
class Actions extends Component
{
    use ExecutesActionsWithContext;

    public TagTeam $tagTeam;

    public function mount(TagTeam $tagTeam): void
    {
        $this->tagTeam = $tagTeam;
    }

    /**
     * Employ a tag team.
     */
    public function employ(): void
    {
        Gate::authorize('employ', $this->tagTeam);

        $this->executeActionWithContext(
            'employed',
            EmployAction::class,
            $this->tagTeam,
            'tag-team',
            fn () => [
                'tag_team_current_status' => $this->tagTeam->status,
                'tag_team_is_employed' => $this->tagTeam->isEmployed(),
                'tag_team_is_suspended' => $this->tagTeam->isSuspended(),
                'tag_team_is_retired' => $this->tagTeam->isRetired(),
            ]
        );
    }

    /**
     * Release a tag team.
     */
    public function release(): void
    {
        Gate::authorize('release', $this->tagTeam);

        $this->executeActionWithContext(
            'released',
            ReleaseAction::class,
            $this->tagTeam,
            'tag-team',
            fn () => [
                'tag_team_current_status' => $this->tagTeam->status,
                'tag_team_is_employed' => $this->tagTeam->isEmployed(),
                'tag_team_is_suspended' => $this->tagTeam->isSuspended(),
            ]
        );
    }

    /**
     * Retire a tag team.
     */
    public function retire(): void
    {
        Gate::authorize('retire', $this->tagTeam);

        $this->executeActionWithContext(
            'retired',
            RetireAction::class,
            $this->tagTeam,
            'tag-team',
            fn () => [
                'tag_team_current_status' => $this->tagTeam->status,
                'tag_team_is_employed' => $this->tagTeam->isEmployed(),
                'tag_team_is_suspended' => $this->tagTeam->isSuspended(),
            ]
        );
    }

    /**
     * Unretire a tag team.
     */
    public function unretire(): void
    {
        Gate::authorize('unretire', $this->tagTeam);

        $this->executeActionWithContext(
            'unretired',
            UnretireAction::class,
            $this->tagTeam,
            'tag-team',
            fn () => [
                'tag_team_current_status' => $this->tagTeam->status,
                'tag_team_is_retired' => $this->tagTeam->isRetired(),
            ]
        );
    }

    /**
     * Suspend a tag team.
     */
    public function suspend(): void
    {
        Gate::authorize('suspend', $this->tagTeam);

        $this->executeActionWithContext(
            'suspended',
            SuspendAction::class,
            $this->tagTeam,
            'tag-team',
            fn () => [
                'tag_team_current_status' => $this->tagTeam->status,
                'tag_team_is_employed' => $this->tagTeam->isEmployed(),
            ]
        );
    }

    /**
     * Reinstate a tag team.
     */
    public function reinstate(): void
    {
        Gate::authorize('reinstate', $this->tagTeam);

        $this->executeActionWithContext(
            'reinstated',
            ReinstateAction::class,
            $this->tagTeam,
            'tag-team',
            fn () => [
                'tag_team_current_status' => $this->tagTeam->status,
                'tag_team_is_suspended' => $this->tagTeam->isSuspended(),
            ]
        );
    }

    /**
     * Delete a tag team.
     */
    public function delete(): void
    {
        Gate::authorize('delete', $this->tagTeam);

        $this->executeActionWithContext(
            'deleted',
            DeleteAction::class,
            $this->tagTeam,
            'tag-team',
            fn () => [
                'tag_team_current_status' => $this->tagTeam->status,
                'tag_team_is_employed' => $this->tagTeam->isEmployed(),
                'tag_team_is_retired' => $this->tagTeam->isRetired(),
                'tag_team_is_suspended' => $this->tagTeam->isSuspended(),
            ]
        );
    }

    /**
     * Restore a soft-deleted tag team.
     */
    public function restore(): void
    {
        Gate::authorize('restore', $this->tagTeam);

        $this->executeActionWithContext(
            'restored',
            RestoreAction::class,
            $this->tagTeam,
            'tag-team',
            fn () => [
                'tag_team_is_deleted' => ! is_null($this->tagTeam->deleted_at),
            ]
        );
    }

    public function render(): View
    {
        return view('livewire.tag-teams.components.actions');
    }
}
