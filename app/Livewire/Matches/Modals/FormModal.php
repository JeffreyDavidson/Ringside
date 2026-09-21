<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Modals;

use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\UpdateMatchAction;
use App\Enums\BusinessRuleReason;
use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
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
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * @extends BaseFormModal<CreateEditForm, EventMatch>
 *
 * @property-read array<string,string> $getMatchTypes
 * @property-read array<int|string,string|null> $getTitles
 * @property-read array<int|string,string|null> $getWrestlers
 * @property-read array<int|string,string|null> $getReferees
 * @property-read array<int|string,string|null> $getTagTeams
 * @property-read array<int, string> $getMatchStipulations
 * @property-read bool $matchTypeAllowsTagTeams
 * @property-read CompetitorSelectionLayout|null $competitorSelectionLayout
 */
class FormModal extends BaseFormModal
{
    use PresentsMatchTypesList;
    use PresentsRefereesList;
    use PresentsTagTeamsList;
    use PresentsTitlesList;
    use PresentsWrestlersList;

    #[\Override]
    protected ?string $createdEventName = 'matchCreated';

    #[\Override]
    protected ?string $updatedEventName = 'matchUpdated';

    #[\Override]
    protected bool $resetFormAfterSubmission = true;

    #[Locked]
    public int $eventId = 0;

    public CreateEditForm $form;

    private MatchFormDummyData $dummyData;

    private AddMatchForEventAction $addMatchForEventAction;

    private UpdateMatchAction $updateMatchAction;

    #[\Override]
    public function mount(int|string|null $modelId = null, ?int $eventId = null): void
    {
        if ($eventId !== null) {
            $this->eventId = $eventId;
        }

        parent::mount($modelId);
    }

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

    #[\Override]
    protected function storeForm(): bool
    {
        $this->form->validate();

        try {
            if ($this->form->isEditing()) {
                $match = EventMatch::query()->findOrFail($this->form->modelId);
                $storedMatch = $this->updateMatchAction->handle($match, $this->form->toData());
            } else {
                $event = Event::query()->findOrFail($this->eventId);
                $storedMatch = $this->addMatchForEventAction->handle($event, $this->form->toData());
            }
        } catch (InvalidMatchConfigurationException $exception) {
            $field = $exception->reason() === BusinessRuleReason::CurrentChampionMissing
                ? 'form.titles'
                : 'form.configuration';

            $this->addError($field, $exception->getMessage());

            return false;
        }

        $this->form->setModel($storedMatch);

        return true;
    }

    /** @return array<int, string> */
    #[Computed]
    public function getMatchStipulations(): array
    {
        return MatchStipulation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (MatchStipulation $stipulation): array => [
                $stipulation->id => $stipulation->name,
            ])
            ->all();
    }

    protected function populateDummyData(): void
    {
        $this->dummyData->fill($this->form);
    }

    #[\Override]
    public function getModalTitle(): string
    {
        return $this->form->isEditing() ? 'Edit Match' : 'Create Match';
    }

    public function updatedFormMatchType(mixed $value): void
    {
        $matchType = match (true) {
            $value instanceof MatchType => $value,
            is_string($value) => MatchType::tryFrom($value),
            default => null,
        };

        if ($matchType !== null) {
            $this->form->resetCompetitorsFor($matchType);
        }
    }

    #[Computed]
    public function matchTypeAllowsTagTeams(): bool
    {
        return $this->form->matchType?->allowsTagTeams() ?? false;
    }

    #[Computed]
    public function competitorSelectionLayout(): ?CompetitorSelectionLayout
    {
        return $this->form->matchType instanceof MatchType
            ? CompetitorSelectionLayout::forMatchType($this->form->matchType)
            : null;
    }

    public function render(): View
    {
        return view('livewire.matches.modals.form-modal');
    }
}
