<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\Roster\RosterMemberKind;
use App\Livewire\Support\RosterMemberSearch;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Renderless;

/**
 * Serves the roster combobox of a form modal that picks existing wrestlers, tag teams or managers.
 *
 * The modal supplies the kinds it offers; the result is a convenience only, because submission still
 * validates every id server-side.
 */
trait SearchesRosterMembers
{
    /**
     * @return array<int, array{id: int|string, name: string}>
     */
    #[Renderless]
    public function searchRoster(string $kind, string $term): array
    {
        $this->authorizeRosterSearch();

        $memberKind = RosterMemberKind::tryFrom($kind);

        if (! $memberKind instanceof RosterMemberKind || ! in_array($memberKind, $this->searchableRosterKinds(), true)) {
            return [];
        }

        return resolve(RosterMemberSearch::class)->search($memberKind, $term);
    }

    /** @return array<int, RosterMemberKind> */
    abstract protected function searchableRosterKinds(): array;

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, array{id: int|string, name: string}>
     */
    protected function rosterLabels(RosterMemberKind $kind, array $ids): array
    {
        return resolve(RosterMemberSearch::class)->labels($kind, $ids);
    }

    private function authorizeRosterSearch(): void
    {
        $modelClass = $this->getModelClass();

        if ($this->form->isCreating()) {
            Gate::authorize('create', $modelClass);

            return;
        }

        Gate::authorize('update', $modelClass::query()->findOrFail($this->form->modelId));
    }
}
