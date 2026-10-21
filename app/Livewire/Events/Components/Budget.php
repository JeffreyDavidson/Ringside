<?php

declare(strict_types=1);

namespace App\Livewire\Events\Components;

use App\Actions\Events\ArchiveBudgetLineAction;
use App\Actions\Events\CorrectBudgetMovementAction;
use App\Actions\Events\RecordBudgetMovementAction;
use App\Actions\Events\SaveBudgetLineAction;
use App\Data\Events\BudgetCorrectionData;
use App\Data\Events\BudgetLineData;
use App\Data\Events\BudgetMovementData;
use App\Enums\Events\BudgetCategory;
use App\Enums\Events\BudgetDirection;
use App\Enums\Events\BudgetMovementKind;
use App\Enums\Events\BudgetOperation;
use App\Exceptions\Events\BudgetException;
use App\Exceptions\RecordChangedException;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Livewire\Events\Forms\BudgetLineForm;
use App\Livewire\Events\Forms\BudgetMovementForm;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Queries\Events\BudgetRevisionHistoryQuery;
use App\ValueObjects\UsdAmount;
use App\ViewModels\Events\EventBudgetViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Budget extends Component
{
    use ExplainsKeptInput;
    use WithPagination;

    #[Locked]
    public int $eventId;

    #[Locked]
    public ?BudgetOperation $operation = null;

    #[Locked]
    public ?int $lineId = null;

    #[Locked]
    public ?int $movementId = null;

    #[Locked]
    public int $draftVersion = 0;

    #[Locked]
    public string $requestKey = '';

    #[Locked]
    public string $draftLabel = '';

    #[Locked]
    public bool $historyOpen = false;

    #[Locked]
    public ?int $historyLineId = null;

    public BudgetLineForm $lineForm;

    public BudgetMovementForm $movementForm;

    public string $reason = '';

    public bool $replaceMovement = false;

    public string $filter = 'all';

    public function mount(int $eventId): void
    {
        $this->eventId = $eventId;
        Gate::authorize('viewBudget', $this->event());
    }

    public function beginLine(?int $lineId = null, string $direction = 'expense'): void
    {
        Gate::authorize('updateBudget', $this->event());
        Validator::make(['direction' => $direction], ['direction' => [Rule::enum(BudgetDirection::class)]])->validate();
        $this->open(BudgetOperation::SaveLine, $lineId);
        $this->lineForm->direction = $direction;
        $this->lineForm->category = $direction === 'income' ? 'tickets' : 'talent';
        if ($lineId !== null) {
            $line = $this->line($lineId);
            $this->draftLabel = $line->description;
            $this->lineForm->fill(['direction' => $line->direction->value, 'category' => $line->category->value,
                'description' => $line->description, 'counterparty' => $line->counterparty,
                'planned' => $line->planned_cents === null ? '' : UsdAmount::decimal($line->planned_cents),
                'confirmed' => $line->confirmed_cents === null ? '' : UsdAmount::decimal($line->confirmed_cents),
                'due_date' => $line->due_date?->toDateString() ?? '', 'notes' => $line->notes ?? '']);
        }
    }

    public function beginMovement(int $lineId, string $kind = 'payment', ?int $movementId = null): void
    {
        Gate::authorize('updateBudget', $this->event());
        Validator::make(['kind' => $kind], ['kind' => [Rule::enum(BudgetMovementKind::class)]])->validate();
        $line = $this->line($lineId);
        $this->open($movementId === null ? BudgetOperation::RecordMovement : BudgetOperation::CorrectMovement, $lineId);
        $this->movementId = $movementId;
        $this->draftLabel = $line->description.' · '.$line->counterparty;
        $this->movementForm->kind = $kind;
        $this->movementForm->effective_date = now($line->budget->timezone)->toDateString();
        if ($movementId !== null) {
            $movement = $line->movements()->whereKey($movementId)->firstOrFail();
            $this->movementForm->fill(['kind' => $movement->kind->value, 'amount' => UsdAmount::decimal($movement->amount_cents),
                'effective_date' => $movement->effective_date->toDateString(), 'method' => $movement->method->value,
                'reference' => $movement->reference ?? '', 'notes' => $movement->notes ?? '', 'source_id' => $movement->source_id]);
        }
    }

    public function updatedMovementFormKind(string $kind): void
    {
        Gate::authorize('updateBudget', $this->event());
        if ($kind === 'payment') {
            $this->movementForm->source_id = null;
        }
    }

    public function beginArchive(int $lineId): void
    {
        Gate::authorize('updateBudget', $this->event());
        $line = $this->line($lineId);
        $this->open(BudgetOperation::ArchiveLine, $lineId);
        $this->draftLabel = $line->description;
    }

    public function cancelDraft(): void
    {
        Gate::authorize('viewBudget', $this->event());
        $this->dispatch('budget-draft-closed');
        $this->operation = null;
        $this->lineForm->reset();
        $this->movementForm->reset();
        $this->reason = '';
        $this->resetErrorBag();
    }

    public function refreshState(): void
    {
        Gate::authorize('viewBudget', $this->event());
        $this->draftVersion = EventBudget::query()->forEventId($this->eventId)->first()->version ?? 0;
        $this->dispatch('budget-refreshed');
    }

    public function showHistory(?int $lineId = null): void
    {
        Gate::authorize('viewBudget', $this->event());
        if ($lineId !== null) {
            $this->line($lineId);
        }
        $this->historyLineId = $lineId;
        $this->historyOpen = true;
        $this->resetPage('history');
    }

    public function hideHistory(): void
    {
        Gate::authorize('viewBudget', $this->event());
        $this->historyOpen = false;
    }

    public function save(
        SaveBudgetLineAction $saveLine,
        RecordBudgetMovementAction $recordMovement,
        CorrectBudgetMovementAction $correctMovement,
        ArchiveBudgetLineAction $archiveLine,
    ): void {
        $event = $this->event();
        Gate::authorize('updateBudget', $event);
        if (! $this->operation instanceof BudgetOperation) {
            $this->addError('budget', __('event-budget.draft_required'));

            return;
        }
        $lineInput = $this->operation === BudgetOperation::SaveLine ? $this->lineForm->validate() : [];
        $movementInput = $this->operation->needsMovementInput($this->replaceMovement)
            ? $this->movementForm->validate()
            : [];
        if ($this->operation->requiresReason()) {
            Validator::make(['reason' => $this->reason], ['reason' => ['required', 'string', 'max:1000']])->validate();
        }
        try {
            match ($this->operation) {
                BudgetOperation::SaveLine => $saveLine->handle($event, $this->draftVersion, $this->requestKey, $this->lineId, BudgetLineData::fromValidated($lineInput)),
                BudgetOperation::RecordMovement => $recordMovement->handle($event, $this->draftVersion, $this->requestKey, $this->lineId ?? 0, BudgetMovementData::fromValidated($movementInput)),
                BudgetOperation::CorrectMovement => $correctMovement->handle($event, $this->draftVersion, $this->requestKey, $this->lineId ?? 0, new BudgetCorrectionData($this->movementId ?? 0, $this->reason, $this->replaceMovement ? BudgetMovementData::fromValidated($movementInput) : null)),
                BudgetOperation::ArchiveLine => $archiveLine->handle($event, $this->draftVersion, $this->requestKey, $this->lineId ?? 0, $this->reason),
            };
        } catch (BudgetException|RecordChangedException $exception) {
            $this->addError('budget', $this->failureMessageKeepingInput($exception));

            return;
        }
        $this->cancelDraft();
        $this->dispatch('budget-saved');
    }

    public function render(EventBudgetViewModel $screen, BudgetRevisionHistoryQuery $historyQuery): View
    {
        $event = $this->event();
        Gate::authorize('viewBudget', $event);
        $data = $screen->data($event);
        $direction = BudgetDirection::tryFrom($this->lineForm->direction);
        $categories = array_filter(BudgetCategory::cases(), fn (BudgetCategory $category): bool => $direction !== null && $category->accepts($direction));
        $history = null;
        if ($this->historyOpen && $data['budget'] !== null) {
            $history = $historyQuery->get($data['budget'], $this->historyLineId)->paginate(20, pageName: 'history');
        }
        $selectedLine = $this->lineId === null || $data['budget'] === null ? null : $data['budget']->lines->find($this->lineId);

        return view('livewire.events.components.budget', [...$data, 'categories' => $categories, 'history' => $history,
            'historyChanges' => $screen->historyChanges($history ?? []),
            'selectedLine' => $selectedLine, 'safeFilter' => in_array($this->filter, ['all', 'needs_amount', 'to_pay', 'to_collect', 'refunds'], true) ? $this->filter : 'all']);
    }

    private function event(): Event
    {
        return Event::query()->whereKey($this->eventId)->firstOrFail();
    }

    private function line(int $id): EventBudgetLine
    {
        $budget = EventBudget::query()->forEventId($this->eventId)->firstOrFail();

        return $budget->lines()->with('budget')->whereKey($id)->firstOrFail();
    }

    private function open(BudgetOperation $operation, ?int $lineId): void
    {
        $this->operation = $operation;
        $this->lineId = $lineId;
        $this->movementId = null;
        $this->draftLabel = '';
        $this->lineForm->reset();
        $this->movementForm->reset();
        $this->replaceMovement = false;
        $this->reason = '';
        $this->requestKey = (string) Str::uuid();
        $this->draftVersion = EventBudget::query()->forEventId($this->eventId)->first()->version ?? 0;
        $this->resetErrorBag();
        $this->dispatch('budget-draft-opened');
    }
}
