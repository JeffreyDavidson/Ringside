<?php

declare(strict_types=1);

namespace App\Livewire\Tournaments\Forms;

use App\Data\Tournaments\TournamentBookingData;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentOperation;
use App\Models\Events\Event;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;
use App\Models\Tournaments\Tournament;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class TournamentOperationForm extends Form
{
    /** The drafted operation's value, or an empty string when no draft is open. */
    #[Locked]
    public string $operation = '';

    /** The tournament's promotion, set when a draft begins because a form cannot be injected with it. */
    #[Locked]
    public int $promotionId = 0;

    /** The tournament's entrant kind value, set when a draft begins. */
    #[Locked]
    public string $entrantKind = 'wrestler';

    public ?int $eventId = null;

    /** @var list<int|string> */
    public array $refereeIds = [];

    public ?int $titleId = null;

    public ?int $stipulationId = null;

    public ?int $replacementId = null;

    public string $reason = '';

    public bool $confirmed = false;

    /** Opens a draft of the operation for the tournament with every input cleared except the event. */
    public function start(Tournament $tournament, TournamentOperation $operation, ?int $eventId = null): void
    {
        $this->operation = $operation->value;
        $this->promotionId = $tournament->promotion_id;
        $this->entrantKind = $tournament->entrant_kind->value;
        $this->eventId = $eventId;
        $this->refereeIds = [];
        $this->titleId = null;
        $this->stipulationId = null;
        $this->replacementId = null;
        $this->reason = '';
        $this->confirmed = false;
    }

    /** Closes the draft. */
    public function close(): void
    {
        $this->operation = '';
    }

    /** The validated booking inputs, with the chosen models loaded for the Action. */
    public function bookingData(): TournamentBookingData
    {
        return new TournamentBookingData(
            Event::query()->findOrFail($this->eventId),
            Referee::query()->whereKey($this->refereeIds)->get(),
            Title::query()->whereKey($this->titleId ?? [])->get(),
            $this->stipulationId === null ? null : MatchStipulation::query()->findOrFail($this->stipulationId),
        );
    }

    /**
     * The fields the drafted operation needs, checked before any Action runs.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $operation = TournamentOperation::tryFrom($this->operation);
        $rules = [];

        if ($operation?->needsReason()) {
            $rules['reason'] = ['required', 'string', 'max:2000'];
            $rules['confirmed'] = ['accepted'];
        }

        if (in_array($operation, [TournamentOperation::Book, TournamentOperation::Plan], true)) {
            $rules['eventId'] = [
                $operation === TournamentOperation::Book ? 'required' : 'nullable',
                'integer',
                Rule::exists('events', 'id')->where('promotion_id', $this->promotionId)->whereNull('deleted_at'),
            ];
        }

        if ($operation === TournamentOperation::Book) {
            $rules['refereeIds'] = ['required', 'list', 'min:1'];
            $rules['refereeIds.*'] = [
                'integer',
                'distinct',
                Rule::exists('referees', 'id')->where('promotion_id', $this->promotionId),
            ];
            $rules['titleId'] = ['nullable', 'integer', Rule::exists('titles', 'id')->where('promotion_id', $this->promotionId)];
            $rules['stipulationId'] = ['nullable', 'integer', Rule::exists('matches_stipulations', 'id')];
        }

        if ($operation === TournamentOperation::Replace) {
            $rules['replacementId'] = [
                'required',
                'integer',
                Rule::exists((new (EntrantKind::from($this->entrantKind)->modelClass()))->getTable(), 'id')->where('promotion_id', $this->promotionId),
            ];
        }

        return $rules;
    }
}
