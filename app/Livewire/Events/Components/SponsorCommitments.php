<?php

declare(strict_types=1);

namespace App\Livewire\Events\Components;

use App\Actions\Events\SaveSponsorCommitmentAction;
use App\Actions\Events\SaveSponsorInstructionAction;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Livewire\Events\Forms\SponsorCommitmentForm;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use App\Queries\Events\SponsorInstructionQuery;
use App\ViewModels\EventPackViewModel;
use App\ViewModels\SponsorCommitmentsViewModel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class SponsorCommitments extends Component
{
    use ExplainsKeptInput;
    use WithPagination;

    #[Locked]
    public int $eventId;

    #[Locked]
    public ?int $draftId = null;

    #[Locked]
    public int $draftVersion = 0;

    #[Locked]
    public string $submissionKey = '';

    #[Locked]
    public ?int $historyId = null;

    #[Locked]
    public ?int $instructionId = null;

    #[Locked]
    public int $instructionVersion = 0;

    #[Locked]
    public int $previewPackVersion = 0;

    #[Locked]
    public string $previewFingerprint = '';

    #[Locked]
    public string $previewHash = '';

    #[Locked]
    public string $previewMode = '';

    #[Locked]
    public ?string $previewTarget = null;

    #[Locked]
    public ?int $previewAfter = null;

    public SponsorCommitmentForm $form;

    public bool $formShown = false;

    public string $filter = 'pending';

    public string $sponsorSearch = '';

    public string $mode = 'setup';

    public string $targetKey = '';

    public string $afterMatchId = '';

    public string $instructionReason = '';

    public string $notice = '';

    public function mount(int $eventId): void
    {
        $this->eventId = $eventId;
        Gate::authorize('manageSponsors', $this->event());
    }

    public function newCommitment(): void
    {
        $event = $this->event();
        Gate::authorize('manageSponsors', $event);
        if ($event->trashed() || $event->promotion_id === null) {
            $this->addError('save', __('sponsors.event_unavailable'));

            return;
        }
        $this->form->reset();
        $this->form->timezone = Promotion::zoneOf($event->loadMissing('promotion')->promotion);
        $this->draftId = null;
        $this->draftVersion = 0;
        $this->submissionKey = (string) Str::uuid();
        $this->formShown = true;
        $this->dispatch('sponsor-draft-opened');
        $this->notice = '';
        $this->resetErrorBag();
    }

    public function edit(int $id): void
    {
        Gate::authorize('manageSponsors', $this->event());
        $commitment = $this->commitment($id);
        $this->form->reset();
        $this->form->load($commitment);
        $this->draftId = $id;
        $this->draftVersion = $commitment->version;
        $this->submissionKey = $commitment->submission_key;
        $this->formShown = true;
        $this->dispatch('sponsor-draft-opened');
        $this->resetErrorBag();
    }

    public function chooseSponsor(int $id): void
    {
        $event = $this->event();
        Gate::authorize('manageSponsors', $event);
        $sponsor = Sponsor::query()->where('promotion_id', $event->promotion_id)->whereNull('archived_at')->findOrFail($id);
        $this->form->sponsorId = $sponsor->id;
        $this->sponsorSearch = '';
    }

    public function save(): void
    {
        $event = $this->event();
        Gate::authorize('manageSponsors', $event);
        $data = $this->form->toData();
        Validator::make(['sponsorId' => $data->sponsorId], ['sponsorId' => [Rule::exists('sponsors', 'id')->where('promotion_id', $event->promotion_id)]])->validate();
        try {
            app(SaveSponsorCommitmentAction::class)->handle($event, $this->draftId, $this->draftVersion, $this->submissionKey, $data, $this->form->reason ?: null, $this->form->reviewed, $this->form->refreshSponsorName);
            $this->formShown = false;
            $this->notice = __('sponsors.promise_saved');
            $this->resetPage('commitmentsPage');
        } catch (BaseBusinessException $exception) {
            $this->addError('save', $this->failureMessageKeepingInput($exception));
        }
    }

    public function cancelDraft(): void
    {
        Gate::authorize('manageSponsors', $this->event());
        $this->formShown = false;
        $this->instructionId = null;
        $this->previewHash = '';
        $this->resetErrorBag();
    }

    public function showHistory(int $id): void
    {
        Gate::authorize('manageSponsors', $this->event());
        $this->commitment($id);
        $this->historyId = $id;
        $this->resetPage('historyPage');
    }

    public function beginInstruction(int $id): void
    {
        Gate::authorize('manageSponsors', $this->event());
        $commitment = $this->commitment($id);
        $this->instructionId = $id;
        $this->mode = $commitment->pack_key === null ? ($commitment->type->value === 'announcement' ? 'segment' : 'setup') : explode(':', $commitment->pack_key, 2)[0];
        $this->targetKey = $commitment->pack_key ?? '';
        $pack = $this->event()->pack()->first()->content ?? [];
        $target = app(SponsorInstructionQuery::class)->target($pack, $commitment->pack_key);
        $this->afterMatchId = isset($target['after_match_id']) ? (string) $target['after_match_id'] : '';
        $this->previewHash = '';
        $this->instructionReason = '';
        $this->dispatch('sponsor-draft-opened');
        $this->resetErrorBag();
    }

    public function previewInstruction(): void
    {
        $event = $this->event();
        Gate::authorize('manageSponsors', $event);
        Validator::make(['mode' => $this->mode, 'targetKey' => $this->targetKey, 'afterMatchId' => $this->afterMatchId, 'instructionReason' => $this->instructionReason],
            ['mode' => [Rule::in(['segment', 'setup', 'remove'])], 'targetKey' => ['nullable', 'string', 'max:80'],
                'afterMatchId' => ['nullable', 'integer'], 'instructionReason' => ['nullable', 'string', 'max:2000']])->validate();
        $commitment = $this->commitment($this->instructionId ?? 0);
        $data = app(EventPackViewModel::class)->data($event);
        $this->instructionVersion = $commitment->version;
        $this->previewPackVersion = $data['pack']->version ?? 0;
        $this->previewFingerprint = $data['card']['fingerprint'];
        $this->previewMode = $this->mode;
        $this->previewTarget = $this->mode === 'remove' ? null : ($this->targetKey ?: null);
        $this->previewAfter = $this->mode === 'segment' && $this->afterMatchId !== '' ? (int) $this->afterMatchId : null;
        $this->previewHash = app(SponsorInstructionQuery::class)->preview($event, $commitment, $this->previewMode, $this->previewTarget, $this->previewAfter)['hash'];
    }

    public function saveInstruction(): void
    {
        $event = $this->event();
        Gate::authorize('manageSponsors', $event);
        Validator::make(['reason' => $this->instructionReason], ['reason' => ['nullable', 'string', 'max:2000']])->validate();
        try {
            app(SaveSponsorInstructionAction::class)->handle($event, $this->instructionId ?? 0, $this->instructionVersion, $this->previewPackVersion,
                $this->previewFingerprint, $this->previewMode, $this->previewTarget, $this->previewAfter, $this->previewHash, $this->instructionReason ?: null);
            $this->instructionId = null;
            $this->previewHash = '';
            $this->notice = __('sponsors.instruction_saved');
        } catch (BaseBusinessException $exception) {
            $this->addError('save', $this->failureMessageKeepingInput($exception));
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['mode', 'targetKey', 'afterMatchId'], true)) {
            $this->previewHash = '';
        }
        if ($property === 'filter') {
            $this->resetPage('commitmentsPage');
        }
    }

    public function render(): View
    {
        $event = $this->event();
        Gate::authorize('manageSponsors', $event);
        $screen = app(SponsorCommitmentsViewModel::class);
        $filter = in_array($this->filter, ['pending', 'completed', 'cancelled', 'all'], true) ? $this->filter : 'pending';
        $sponsors = Sponsor::query()->where('promotion_id', $event->promotion_id)->whereNull('archived_at')
            ->whereLike('name', '%'.mb_substr($this->sponsorSearch, 0, 80).'%')->orderBy('name')->orderBy('id')->limit(20)->get(['id', 'name', 'contact_name']);
        $selected = $this->form->sponsorId === 0 ? null : Sponsor::query()->where('promotion_id', $event->promotion_id)->find($this->form->sponsorId);
        $instruction = $this->instructionId === null ? null : $this->commitment($this->instructionId);
        $history = $this->historyId === null ? null : $this->commitment($this->historyId)->revisions()->orderByDesc('version')->paginate(10, pageName: 'historyPage');

        $preview = ! $instruction instanceof EventSponsorCommitment || $this->previewHash === '' ? null : app(SponsorInstructionQuery::class)->preview($event, $instruction, $this->previewMode, $this->previewTarget, $this->previewAfter);

        return view('livewire.events.components.sponsor-commitments', ['event' => $event, 'rows' => $screen->rows($event, $filter),
            'screen' => $screen, 'sponsors' => $sponsors, 'selected' => $selected, 'history' => $history, 'instruction' => $instruction,
            'preview' => $preview, 'packData' => $instruction instanceof EventSponsorCommitment ? app(EventPackViewModel::class)->data($event) : null]);
    }

    private function event(): Event
    {
        return Event::query()->withTrashed()->findOrFail($this->eventId);
    }

    private function commitment(int $id): EventSponsorCommitment
    {
        return EventSponsorCommitment::query()->where('event_id', $this->eventId)->findOrFail($id);
    }
}
