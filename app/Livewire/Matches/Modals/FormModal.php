<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Modals;

use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\UpdateMatchAction;
use App\Enums\MatchType;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsMatchTypesList;
use App\Livewire\Concerns\Data\PresentsRefereesList;
use App\Livewire\Concerns\Data\PresentsTagTeamsList;
use App\Livewire\Concerns\Data\PresentsTitlesList;
use App\Livewire\Concerns\Data\PresentsWrestlersList;
use App\Livewire\Matches\Enums\CompetitorSelectionLayout;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Livewire\Matches\Support\MatchFormDummyData;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/** @extends BaseFormModal<CreateEditForm, EventMatch> */
class FormModal extends BaseFormModal
{
    use PresentsMatchTypesList;
    use PresentsRefereesList;
    use PresentsTagTeamsList;
    use PresentsTitlesList;
    use PresentsWrestlersList;

    #[Locked]
    public int $eventId = 0;

    public CreateEditForm $form;

    private MatchFormDummyData $dummyData;

    private AddMatchForEventAction $addMatchForEventAction;

    private UpdateMatchAction $updateMatchAction;

    public function boot(
        MatchFormDummyData $dummyData,
        AddMatchForEventAction $addMatchForEventAction,
        UpdateMatchAction $updateMatchAction,
    ): void {
        $this->dummyData = $dummyData;
        $this->addMatchForEventAction = $addMatchForEventAction;
        $this->updateMatchAction = $updateMatchAction;
    }

    protected function getModelClass(): string
    {
        return EventMatch::class;
    }

    protected function storeForm(): bool
    {
        $this->form->validate();

        if ($this->form->isEditing()) {
            $match = EventMatch::query()->findOrFail($this->form->modelId);
            Gate::authorize('update', $match);
            $storedMatch = $this->updateMatchAction->handle($match, $this->form->toData());
        } else {
            Gate::authorize('create', EventMatch::class);
            $event = Event::query()->findOrFail($this->eventId);
            $storedMatch = $this->addMatchForEventAction->handle($event, $this->form->toData());
        }

        $this->form->setModel($storedMatch);

        return true;
    }

    /** @return array<int, string> */
    #[Computed(cache: true, key: 'active-match-stipulations-list', seconds: 180)]
    public function getMatchStipulations(): array
    {
        return MatchStipulation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<string, mixed> */
    protected function getDummyDataFields(): array
    {
        return $this->dummyData->generate();
    }

    public function openModal(mixed $modelId = null): void
    {
        if ($modelId === null) {
            Gate::authorize('create', EventMatch::class);
        } else {
            Gate::authorize('update', EventMatch::query()->findOrFail($modelId));
        }

        parent::openModal($modelId);
    }

    public function getModalTitle(): string
    {
        return isset($this->model) ? 'Edit Match' : 'Create Match';
    }

    public function submitForm(): bool
    {
        $isCreating = $this->form->isCreating();
        $wasStored = parent::submitForm();

        if (! $wasStored) {
            return false;
        }

        $this->dispatch($isCreating ? 'matchCreated' : 'matchUpdated');
        $this->form->reset();

        return true;
    }

    public function updatedFormMatchType(mixed $value): void
    {
        $matchType = $value instanceof MatchType ? $value : MatchType::tryFrom((string) $value);

        if ($matchType !== null) {
            $this->form->resetCompetitorsFor($matchType);
        }
    }

    public function getMatchTypeAllowsTagTeamsProperty(): bool
    {
        return $this->form->matchType?->allowsTagTeams() ?? false;
    }

    #[Computed]
    public function competitorSelectionLayout(): ?CompetitorSelectionLayout
    {
        return $this->form->matchType === null
            ? null
            : CompetitorSelectionLayout::forMatchType($this->form->matchType);
    }

    public function render(): View
    {
        return view('livewire.matches.modals.form-modal');
    }
}
